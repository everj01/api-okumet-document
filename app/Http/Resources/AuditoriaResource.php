<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'actor_nombre' => $this->actor_nombre,
            'actor_email' => $this->actor_email,
            'tenant' => $this->tenant ? [
                'id' => $this->tenant->id,
                'nombre_comercial' => $this->tenant->nombre_comercial,
            ] : null,
            'accion' => $this->accion,
            'modulo' => $this->modulo,
            'descripcion' => $this->descripcion,
            'ip' => $this->ip,
            'creado_en' => $this->creado_en?->toIso8601String(),
        ];
    }
}
