<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documento\ExtraerPreviaRequest;
use App\Http\Requests\Documento\PreguntaRequest;
use App\Http\Resources\ConsultaResource;
use App\Http\Resources\ExtraccionResource;
use App\Models\Documento;
use App\Models\DocumentoPagina;
use App\Services\AsistenteIa;
use App\Services\LectorPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use Throwable;

class DocumentoIaController extends Controller
{
    public function __construct(
        private readonly AsistenteIa $asistente,
        private readonly LectorPdf $lector,
    ) {}

    public function resumen(Documento $documento): JsonResponse
    {
        $this->authorize('analizar', $documento);
        $this->verificarTexto($documento);

        try {
            $resumen = $this->asistente->resumir($documento->load('paginasTexto'));
        } catch (IaException $e) {
            return $this->error($e);
        }

        $documento->update(['resumen' => $resumen]);

        return response()->json(['resumen' => $resumen]);
    }

    public function preguntar(PreguntaRequest $request, Documento $documento): JsonResponse
    {

        Log::info('El usuario ha registrado una pregunta.');

        $this->authorize('analizar', $documento);
        $this->verificarTexto($documento);

        Log::info('El sistema a terminado de validar los parametros y el documento.');

        try {
            $salida = $this->asistente->preguntar($documento->load('paginasTexto'), $request->pregunta);
            Log::info('Preguntamos a Claude acerca del documento');
        } catch (IaException $e) {
            Log::info('Error encontrado en el try catch.'.$this->error($e));
            return $this->error($e);
        }

        $consulta = $documento->consultas()->create([
            'usuario_id' => $request->user()->id,
            'pregunta' => $request->pregunta,
            'respuesta' => $salida['respuesta'],
            'paginas' => $salida['paginas'],
        ]);

        return (new ConsultaResource($consulta->load('usuario')))->response()->setStatusCode(201);
    }

    // Devuelve la extracción guardada si existe; `?refrescar=1` vuelve a analizar (y vuelve a costar).
    public function extraccion(Request $request, Documento $documento): JsonResponse
    {
        $this->authorize('analizar', $documento);

        $guardada = $documento->extraccion;

        if ($guardada !== null && ! $request->boolean('refrescar')) {
            return (new ExtraccionResource($guardada))->response();
        }

        $this->verificarTexto($documento);

        try {
            $datos = $this->asistente->extraer($documento->load('paginasTexto'));
        } catch (IaException $e) {
            return $this->error($e);
        }

        $extraccion = $documento->extraccion()->updateOrCreate(
            ['documento_id' => $documento->id],
            $datos,
        );

        return (new ExtraccionResource($extraccion))->response();
    }

    // Analiza un PDF sin crear ningún registro: lo usa el formulario de "nuevo expediente" para
    // autocompletarse antes de que el usuario confirme la creación. El archivo se descarta al terminar.
    public function extraerPrevia(ExtraerPreviaRequest $request): JsonResponse
    {
        $vacio = ['campos' => (object) [], 'partes' => [], 'fechas_clave' => []];

        try {
            $paginas = $this->lector->extraerPaginas($request->file('archivo')->getRealPath());
        } catch (Throwable $e) {
            Log::warning("No se pudo leer el PDF en extracción previa: {$e->getMessage()}");

            return response()->json(['data' => $vacio]);
        }

        if ($paginas === []) {
            return response()->json(['data' => $vacio]);
        }

        $documento = new Documento(['paginas' => count($paginas)]);
        $documento->setRelation(
            'paginasTexto',
            collect($paginas)->map(
                fn (string $texto, int $numero) => new DocumentoPagina(['numero' => $numero, 'texto' => $texto])
            )->values()
        );

        try {
            $datos = $this->asistente->extraer($documento);
        } catch (IaException $e) {
            return response()->json(['data' => $vacio]);
        }

        return response()->json(['data' => [
            'campos' => (object) $datos['campos'],
            'partes' => $datos['partes'],
            'fechas_clave' => $datos['fechas_clave'],
        ]]);
    }

    private function verificarTexto(Documento $documento): void
    {
        abort_if($documento->paginas === 0, 422, 'El documento no tiene texto extraído.');
    }

    private function error(IaException $e): JsonResponse
    {
        $cuerpo = ['message' => $e->getMessage()];

        if ($e->codigo !== null) {
            $cuerpo['code'] = $e->codigo;
        }

        return response()->json($cuerpo, $e->status);
    }
}
