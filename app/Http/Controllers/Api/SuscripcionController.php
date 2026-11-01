<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Http\Resources\SuscripcionResource;
use App\Services\AlmacenamientoService;
use App\Services\LimiteClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuscripcionController extends Controller
{
    public function __construct(
        private readonly AlmacenamientoService $almacenamiento,
        private readonly LimiteClaudeService $limiteClaude,
    ) {}

    // Mi plan + uso + estado de bloqueo: lo que el frontend necesita tanto para el topbar normal
    // como para decidir si muestra la pantalla de "renueva tu plan".
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        $suscripcion = $tenant->suscripcionActual();

        return response()->json([
            'plan' => $suscripcion?->plan ? new PlanResource($suscripcion->plan) : null,
            'suscripcion' => $suscripcion ? new SuscripcionResource($suscripcion) : null,
            'bloqueado' => $tenant->estaBloqueado(),
            'almacenamiento' => $this->almacenamiento->estado($tenant),
            'ia' => $this->limiteClaude->estadoTenant($tenant),
        ]);
    }
}
