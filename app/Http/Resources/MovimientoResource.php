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
            // Un movimiento sin usuario (registrado_por null) es siempre generado por el sistema: nunca lo crea un manual.
            'registrado_por' => $this->registrado_por === null
                ? 'Sistema'
                : $this->whenLoaded('registradoPor', fn () => $this->registradoPor?->name),
        ];
    }
}
