<?php

namespace App\Http\Controllers\Api;

use App\Events\DocumentoActualizado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documento\AsignarExpedienteRequest;
use App\Http\Requests\Documento\SincronizarEtiquetasRequest;
use App\Http\Requests\Documento\StoreDocumentoRequest;
use App\Http\Requests\Documento\StoreDocumentosSinExpedienteRequest;
use App\Http\Resources\DocumentoResource;
use App\Models\Documento;
use App\Models\DocumentoHistorial;
use App\Models\Expediente;
use App\Services\AlmacenamientoService;
use App\Services\LectorPdf;
use App\Services\RutaDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $documentos = Documento::select(['id','uuid', 'expediente_id', 'nombre', 'ruta', 'tamano', 'paginas', 'resumen', 'subido_por', 'created_at', 'updated_at'])
            ->with(['expediente', 'subidoPor', 'etiquetas'])
            ->visiblePara($request->user())
            ->buscar($request->input('buscar'))
            ->when($request->filled('expediente_id'), fn ($query) => $query->where('expediente_id', $request->integer('expediente_id')))
            ->when($request->filled('etiqueta_id'), fn ($query) => $query->whereHas('etiquetas', fn ($q) => $q->where('etiquetas.id', $request->integer('etiqueta_id'))))
            ->when($request->boolean('solo_huerfanos'), fn ($query) => $query->whereNull('expediente_id'))
            ->latest()
            ->paginate($request->integer('por_pagina', 10))
            ->withQueryString();

        return DocumentoResource::collection($documentos);
    }

    public function store(
        StoreDocumentoRequest $request,
        Expediente $expediente,
        LectorPdf $lector,
        AlmacenamientoService $almacenamiento,
    ): JsonResponse {
        $this->authorize('update', $expediente);

        $archivo = $request->file('archivo');
        $almacenamiento->verificarEspacioPara($request->user()->tenant, $archivo->getSize(), 'archivo');

        $ruta = $archivo->store("expedientes/{$expediente->id}", 'documentos');

        $documento = $expediente->documentos()->create([
            'nombre' => $request->input('nombre') ?: $archivo->getClientOriginalName(),
            'ruta' => $ruta,
            'tamano' => $archivo->getSize(),
            'subido_por' => $request->user()->id,
        ]);

        $this->guardarTexto($documento, $lector);
        $documento->load(['expediente', 'subidoPor', 'etiquetas']);

        broadcast(new DocumentoActualizado($documento, 'creado'))->toOthers();

        return (new DocumentoResource($documento))->response()->setStatusCode(201);
    }

    // Subida sin expediente ("huérfano"): varios archivos a la vez, sin correr IA todavía.
    public function storeSinExpediente(
        StoreDocumentosSinExpedienteRequest $request,
        RutaDocumentoService $rutas,
        LectorPdf $lector,
        AlmacenamientoService $almacenamiento,
    ): JsonResponse {
        $archivos = collect($request->file('archivos'));

        $almacenamiento->verificarEspacioPara(
            $request->user()->tenant,
            $archivos->sum(fn ($archivo) => $archivo->getSize()),
            'archivos',
        );

        $carpeta = $rutas->carpetaHuerfano();

        $documentos = $archivos->map(function ($archivo) use ($request, $carpeta, $lector) {
            $ruta = $archivo->store($carpeta, 'documentos');

            $documento = Documento::create([
                'nombre' => $archivo->getClientOriginalName(),
                'ruta' => $ruta,
                'tamano' => $archivo->getSize(),
                'subido_por' => $request->user()->id,
            ]);

            $this->guardarTexto($documento, $lector);

            $documento->historial()->create([
                'evento' => DocumentoHistorial::SUBIDO_HUERFANO,
                'usuario_id' => $request->user()->id,
            ]);

            $documento->load(['subidoPor', 'etiquetas']);

            broadcast(new DocumentoActualizado($documento, 'creado'))->toOthers();

            return $documento;
        });

        return DocumentoResource::collection($documentos)->response()->setStatusCode(201);
    }

    // Convierte un documento huérfano en documento de un expediente: mueve el archivo físico y deja
    // registro en el historial. El expediente (y el cliente, si corresponde) ya se crearon antes de
    // llamar a esto, con los endpoints normales de /clientes y /expedientes.
    public function asignarExpediente(
        AsignarExpedienteRequest $request,
        Documento $documento,
        RutaDocumentoService $rutas,
    ): JsonResponse {
        $this->authorize('asignarExpediente', $documento);

        $expedienteId = $request->validated('expediente_id');

        $documento = DB::transaction(function () use ($documento, $expedienteId, $rutas, $request) {
            $bloqueado = Documento::whereKey($documento->id)->lockForUpdate()->first();

            if ($bloqueado->expediente_id !== null) {
                throw ValidationException::withMessages([
                    'expediente_id' => ['Este documento ya fue asignado a un expediente.'],
                ]);
            }

            $rutaDestino = $rutas->carpetaExpediente($expedienteId, $bloqueado->created_at).'/'.basename($bloqueado->ruta);

            if (! Storage::disk('documentos')->move($bloqueado->ruta, $rutaDestino)) {
                throw new RuntimeException("No se pudo mover el archivo del documento {$bloqueado->id} al expediente {$expedienteId}.");
            }

            $bloqueado->update(['expediente_id' => $expedienteId, 'ruta' => $rutaDestino]);

            $bloqueado->historial()->create([
                'evento' => DocumentoHistorial::ASIGNADO_A_EXPEDIENTE,
                'expediente_id' => $expedienteId,
                'usuario_id' => $request->user()->id,
            ]);

            return $bloqueado;
        });

        $documento->load(['expediente', 'subidoPor', 'etiquetas']);

        broadcast(new DocumentoActualizado($documento, 'actualizado'))->toOthers();

        return (new DocumentoResource($documento))->response();
    }

    public function show(Documento $documento): DocumentoResource
    {
        $this->authorize('view', $documento);

        return new DocumentoResource(
            $documento->load(['expediente', 'subidoPor', 'etiquetas', 'consultas.usuario', 'historial.expediente', 'historial.usuario'])
        );
    }

    public function sincronizarEtiquetas(SincronizarEtiquetasRequest $request, Documento $documento): DocumentoResource
    {
        $this->authorize('etiquetar', $documento);

        $documento->etiquetas()->sync($request->validated('etiqueta_ids'));

        return new DocumentoResource($documento->load('etiquetas'));
    }

    public function archivo(Request $request, Documento $documento): BinaryFileResponse
    {
        $this->authorize('view', $documento);

        $ruta = Storage::disk('documentos')->path($documento->ruta);

        abort_unless(is_file($ruta), 404, 'El archivo ya no está disponible.');

        return $request->boolean('descargar')
            ? response()->download($ruta, $documento->nombre.'.pdf')
            : response()->file($ruta);
    }

    public function destroy(Documento $documento): JsonResponse
    {
        $this->authorize('delete', $documento);

        broadcast(new DocumentoActualizado($documento, 'eliminado'))->toOthers();

        Storage::disk('documentos')->delete($documento->ruta);
        $documento->delete();

        return response()->json(['message' => 'Documento eliminado.']);
    }

    // Guarda el texto del PDF: completo para la búsqueda y por página para la IA.
    private function guardarTexto(Documento $documento, LectorPdf $lector): void
    {
        try {
            $paginas = $lector->extraerPaginas(Storage::disk('documentos')->path($documento->ruta));
        } catch (\Throwable $e) {
            Log::warning("No se pudo leer el PDF del documento {$documento->id}: {$e->getMessage()}");

            return;
        }

        DB::transaction(function () use ($documento, $paginas) {
            $documento->update([
                'paginas' => count($paginas),
                'texto' => implode("\n", $paginas),
            ]);

            $documento->paginasTexto()->delete();
            $documento->paginasTexto()->createMany(
                collect($paginas)->map(fn (string $texto, int $numero) => [
                    'numero' => $numero,
                    'texto' => $texto,
                ])->values()->all()
            );
        });
    }
}
