<?php

namespace App\Events;

use App\Http\Resources\DocumentoResource;
use App\Models\Documento;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
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
        // Huérfano (sin expediente todavía): solo quien lo subió lo está mirando en vivo.
        return [$this->documento->expediente_id !== null
            ? new Channel("expediente.{$this->documento->expediente_id}")
            : new PrivateChannel("usuario.{$this->documento->subido_por}")];
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
