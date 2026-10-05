<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tipo_persona' => $this->tipo_persona,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'notas' => $this->notas,
            'activo' => $this->activo,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'nombre_comercial' => $this->tenant->nombre_comercial,
            ]),
            'expedientes_count' => $this->whenCounted('expedientes'),
            'expedientes' => ExpedienteResource::collection($this->whenLoaded('expedientes')),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
