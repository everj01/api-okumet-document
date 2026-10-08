<?php

namespace App\Http\Controllers\Api;

use App\Events\DocumentoActualizado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documento\SincronizarEtiquetasRequest;
use App\Http\Requests\Documento\StoreDocumentoRequest;
use App\Http\Resources\DocumentoResource;
use App\Models\Documento;
use App\Models\Expediente;
use App\Services\LectorPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            ->latest()
            ->paginate($request->integer('por_pagina', 10))
            ->withQueryString();

        return DocumentoResource::collection($documentos);
    }

    public function store(StoreDocumentoRequest $request, Expediente $expediente, LectorPdf $lector): JsonResponse
    {
        $this->authorize('update', $expediente);

        $archivo = $request->file('archivo');
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

    public function show(Documento $documento): DocumentoResource
    {
        $this->authorize('view', $documento);

        return new DocumentoResource($documento->load(['expediente', 'subidoPor', 'etiquetas', 'consultas.usuario']));
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
