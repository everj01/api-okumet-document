<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoHistorialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evento' => $this->evento,
            'expediente' => $this->whenLoaded('expediente', fn () => $this->expediente ? [
                'uuid' => $this->expediente->uuid,
                'codigo' => $this->expediente->codigo,
                'titulo' => $this->expediente->titulo,
            ] : null),
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario?->name),
            'creado_en' => $this->creado_en?->toDateTimeString(),
        ];
    }
}
