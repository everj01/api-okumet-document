<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pregunta' => $this->pregunta,
            'respuesta' => $this->respuesta,
            'paginas' => $this->paginas ?? [],
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario?->name),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
