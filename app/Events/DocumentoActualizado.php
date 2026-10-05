<?php

namespace App\Events;

use App\Http\Resources\DocumentoResource;
use App\Models\Documento;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Documento $documento,
        public string $accion,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("expediente.{$this->documento->expediente_id}")];
    }

    public function broadcastAs(): string
    {
        return 'documento.actualizado';
    }

    public function broadcastWith(): array
    {
        return [
            'accion' => $this->accion,
            'documento' => $this->accion === 'eliminado'
                ? ['id' => $this->documento->id]
                : (new DocumentoResource($this->documento))->resolve(),
        ];
    }
}
