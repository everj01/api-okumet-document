<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'expediente_id' => $this->expediente_id,
            'fecha' => $this->fecha?->toDateString(),
            'titulo' => $this->titulo,
            'detalle' => $this->detalle,
            'registrado_por' => $this->whenLoaded('registradoPor', fn () => $this->registradoPor?->name),
        ];
    }
}
