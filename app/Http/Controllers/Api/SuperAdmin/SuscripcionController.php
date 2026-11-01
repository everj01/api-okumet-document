<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuscripcionResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

// Visibilidad y control manual de la suscripción de un tenant puntual, para soporte técnico.
class SuscripcionController extends Controller
{
    public function show(Tenant $tenant): JsonResponse
    {
        $suscripcion = $tenant->suscripcionActual();

        return response()->json([
            'suscripcion' => $suscripcion ? new SuscripcionResource($suscripcion) : null,
            'bloqueado' => $tenant->estaBloqueado(),
        ]);
    }

    // Transición manual mientras no hay pasarela conectada: soporte marca la suscripción como
    // vencida (ej. venció el periodo pagado) y a partir de ahí corre la gracia de 5 días.
    public function marcarVencida(Tenant $tenant): JsonResponse
    {
        $suscripcion = $tenant->suscripcionActual();

        if (! $suscripcion) {
            return response()->json(['message' => 'El tenant no tiene ninguna suscripción.'], 422);
        }

        $suscripcion->marcarVencida();

        return response()->json(['suscripcion' => new SuscripcionResource($suscripcion->load('plan'))]);
    }
}
