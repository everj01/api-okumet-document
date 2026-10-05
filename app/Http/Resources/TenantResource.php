<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'nombre_comercial' => $this->nombre_comercial,
            'razon_social' => $this->razon_social,
            'ruc' => $this->ruc,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => $this->activo,
            'logo_url' => $this->logo_path ? Storage::disk('logos')->url($this->logo_path) : null,
            'usuarios_count' => $this->whenCounted('usuarios'),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
