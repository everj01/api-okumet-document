<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuscripcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'estado' => $this->estado,
            'inicia_en' => $this->inicia_en?->toDateString(),
            'termina_en' => $this->termina_en?->toDateString(),
            'vencida_en' => $this->vencida_en?->toDateString(),
            'bloqueada' => $this->estaBloqueada(),
        ];
    }
}
