<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\IaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documento\PreguntaRequest;
use App\Http\Resources\ConsultaResource;
use App\Http\Resources\ExtraccionResource;
use App\Models\Documento;
use App\Services\AsistenteIa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class DocumentoIaController extends Controller
{
    public function __construct(private readonly AsistenteIa $asistente) {}

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

    private function verificarTexto(Documento $documento): void
    {
        abort_if($documento->paginas === 0, 422, 'El documento no tiene texto extraído.');
    }

    private function error(IaException $e): JsonResponse
    {
        return response()->json(['message' => $e->getMessage()], $e->status);
    }
}
