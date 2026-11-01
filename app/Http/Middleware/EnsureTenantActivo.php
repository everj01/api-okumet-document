<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Corta el acceso a las funciones del tenant cuando su suscripción está vencida sin renovar
// (Free a los 30 días, o un plan pagado tras agotar los 5 días de gracia). Login, logout y el
// estado de la suscripción quedan fuera de este middleware para que el frontend pueda mostrar
// la pantalla de "renueva tu plan" sin perder la sesión.
class EnsureTenantActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof User && $usuario->tenant?->estaBloqueado()) {
            return response()->json([
                'message' => 'Tu suscripción venció. Renueva tu plan para seguir usando la plataforma.',
                'bloqueado' => true,
            ], 402);
        }

        return $next($request);
    }
}
