<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventoResource;
use App\Http\Resources\ExpedienteResource;
use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Todo el panel se limita a lo que el usuario puede ver.
        $usuario = $request->user();

        $proximos = Evento::with(['expediente', 'responsable'])
            ->visiblePara($usuario)
            ->pendientes()
            ->whereBetween('inicio', [now()->startOfDay(), now()->addDays(15)->endOfDay()])
            ->orderBy('inicio')
            ->limit(8)
            ->get();

        $recientes = Expediente::with(['cliente', 'abogado'])
            ->visiblePara($usuario)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return response()->json([
            'contadores' => [
                'clientes' => Cliente::where('activo', true)->count(),
                'expedientes_abiertos' => Expediente::visiblePara($usuario)->abiertos()->count(),
                'documentos' => Documento::visiblePara($usuario)->count(),
                'eventos_pendientes' => Evento::visiblePara($usuario)->pendientes()->where('inicio', '>=', now())->count(),
            ],
            'vencidos' => EventoResource::collection(
                Evento::with('expediente')->visiblePara($usuario)->pendientes()->where('inicio', '<', now())->orderByDesc('inicio')->limit(5)->get()
            ),
            'proximos_eventos' => EventoResource::collection($proximos),
            'expedientes_recientes' => ExpedienteResource::collection($recientes),
        ]);
    }
}
