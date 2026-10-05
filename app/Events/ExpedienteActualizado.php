<?php

namespace App\Events;

use App\Http\Resources\ExpedienteResource;
use App\Models\Expediente;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpedienteActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Expediente $expediente,
        public string $accion,
    ) {}

    public function broadcastOn(): array
    {
        // El canal de detalle lleva el recurso completo; los de lista solo avisan "algo cambió" (ver broadcastWith).
        $canales = [new Channel("expediente.{$this->expediente->id}")];

        $canales[] = new Channel('expedientes.todos');

        if ($this->expediente->abogado_id) {
            $canales[] = new Channel("expedientes.abogado.{$this->expediente->abogado_id}");
        }

        return $canales;
    }

    public function broadcastAs(): string
    {
        return 'expediente.actualizado';
    }

    public function broadcastWith(): array
    {
        return [
            'accion' => $this->accion,
            'expediente' => $this->accion === 'eliminado'
                ? ['id' => $this->expediente->id]
                : (new ExpedienteResource($this->expediente))->resolve(),
        ];
    }
}
