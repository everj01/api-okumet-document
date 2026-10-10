<?php

namespace App\Events;

use App\Http\Resources\NotificacionResource;
use App\Models\Notificacion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacionCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notificacion $notificacion) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("usuario.{$this->notificacion->usuario_id}")];
    }

    public function broadcastAs(): string
    {
        return 'notificacion.creada';
    }

    public function broadcastWith(): array
    {
        return (new NotificacionResource($this->notificacion))->resolve();
    }
}
