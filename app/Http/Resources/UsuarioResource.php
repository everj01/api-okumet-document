<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'activo' => $this->activo,
            'rol_id' => $this->rol_id,
            'rol' => $this->whenLoaded('rol', fn () => [
                'id' => $this->rol->id,
                'nombre' => $this->rol->nombre,
            ]),
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'uuid' => $this->tenant->uuid,
                'nombre_comercial' => $this->tenant->nombre_comercial,
                'logo_url' => $this->tenant->logo_path ? Storage::disk('logos')->url($this->tenant->logo_path) : null,
            ]),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
