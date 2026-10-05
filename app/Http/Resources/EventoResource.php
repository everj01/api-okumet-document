<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tipo' => $this->tipo,
            'titulo' => $this->titulo,
            'inicio' => $this->inicio?->toDateTimeString(),
            'fin' => $this->fin?->toDateTimeString(),
            'lugar' => $this->lugar,
            'notas' => $this->notas,
            'estado' => $this->estado,
            'recordatorio_dias' => $this->recordatorio_dias,
            'expediente_id' => $this->expediente_id,
            'responsable_id' => $this->responsable_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'nombre_comercial' => $this->tenant->nombre_comercial,
            ]),
            'expediente' => $this->whenLoaded('expediente', fn () => [
                'id' => $this->expediente?->id,
                'codigo' => $this->expediente?->codigo,
                'titulo' => $this->expediente?->titulo,
            ]),
            'responsable' => $this->whenLoaded('responsable', fn () => $this->responsable?->name),
        ];
    }
}
