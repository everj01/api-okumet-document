<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClaudeUsoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param array{usado: int, limite: int, restante: int, advertencia: bool, periodo: string} $estado */
    public function __construct(public User $usuario, public array $estado) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("usuario.{$this->usuario->id}")];
    }

    public function broadcastAs(): string
    {
        return 'claude-uso.actualizado';
    }

    public function broadcastWith(): array
    {
        return $this->estado;
    }
}
