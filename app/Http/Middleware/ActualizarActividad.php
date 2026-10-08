<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Alimenta "usuarios en línea" del panel de super admin. Actualiza por query builder (no Eloquent)
// para no disparar observers ni tocar updated_at, y se throttlea a 1 vez por minuto por usuario
// para no escribir en cada request.
class ActualizarActividad
{
    private const THROTTLE_SEGUNDOS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof User
            && (! $usuario->last_activity_at || $usuario->last_activity_at->diffInSeconds(now()) >= self::THROTTLE_SEGUNDOS)
        ) {
            User::whereKey($usuario->id)->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
