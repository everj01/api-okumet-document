<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => (float) $this->precio,
            'limite_llamadas_claude' => $this->limite_llamadas_claude,
            'limite_usuarios' => $this->limite_usuarios,
            'limite_almacenamiento_mb' => $this->limite_almacenamiento_mb,
            'activo' => $this->activo,
        ];
    }
}
