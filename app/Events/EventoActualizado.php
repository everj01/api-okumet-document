<?php

namespace App\Events;

use App\Http\Resources\EventoResource;
use App\Models\Evento;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Evento $evento,
        public string $accion,
    ) {}

    public function broadcastOn(): array
    {
        // Un evento ligado a un expediente va solo al canal de ese expediente; "agenda" es para los eventos sueltos.
        if ($this->evento->expediente_id) {
            return [new Channel("expediente.{$this->evento->expediente_id}")];
        }

        return [new Channel('agenda')];
    }

    public function broadcastAs(): string
    {
        return 'evento.actualizado';
    }

    public function broadcastWith(): array
    {
        return [
            'accion' => $this->accion,
            'evento' => $this->accion === 'eliminado'
                ? ['id' => $this->evento->id]
                : (new EventoResource($this->evento))->resolve(),
        ];
    }
}
