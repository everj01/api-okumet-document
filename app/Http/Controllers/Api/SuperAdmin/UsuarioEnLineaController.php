<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarioEnLineaController extends Controller
{
    // "En línea" = tuvo un request autenticado en los últimos 5 minutos (ver App\Http\Middleware\ActualizarActividad).
    private const MINUTOS_ACTIVO = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $usuarios = User::with(['tenant', 'rol'])
            ->where('last_activity_at', '>=', now()->subMinutes(self::MINUTOS_ACTIVO))
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (User $usuario) => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'tenant' => $usuario->tenant ? [
                    'id' => $usuario->tenant->id,
                    'nombre_comercial' => $usuario->tenant->nombre_comercial,
                ] : null,
                'rol' => $usuario->rol?->nombre,
                'conectado_desde' => $usuario->last_activity_at?->toIso8601String(),
            ]);

        return response()->json($usuarios);
    }
}
