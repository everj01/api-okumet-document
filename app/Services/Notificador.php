<?php

namespace App\Services;

use App\Events\NotificacionCreada;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

// Único punto que crea una notificación y la emite en tiempo real: cualquier parte del sistema
// que necesite avisarle algo a un usuario pasa por aquí, así el canal/evento no se repite por caso de uso.
class Notificador
{
    public function enviar(User $usuario, string $tipo, string $titulo, string $mensaje, array $data = []): Notificacion
    {
        $notificacion = Notificacion::create([
            'usuario_id' => $usuario->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'data' => $data,
        ]);

        // La notificación ya quedó guardada: si Reverb está caído, el usuario la ve al refrescar en vez
        // de perder la acción real (ej. la respuesta de Claude, o la exportación) que la disparó.
        try {
            broadcast(new NotificacionCreada($notificacion));
        } catch (Throwable $e) {
            Log::warning('notificacion.broadcast_fallo', ['notificacion_id' => $notificacion->id, 'mensaje' => $e->getMessage()]);
        }

        return $notificacion;
    }
}
