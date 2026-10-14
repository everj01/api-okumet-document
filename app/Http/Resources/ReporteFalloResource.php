<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReporteFalloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'motivo' => $this->motivo,
            'comentario' => $this->comentario,
            'ruta' => $this->ruta,
            'estado' => $this->estado,
            'respuesta' => $this->respuesta,
            'resuelto_en' => $this->resuelto_en?->toDateTimeString(),
            // Requiere el bearer token de super-admin para descargarse, igual que documentos/{documento}/archivo.
            'captura_url' => $this->captura_path ? url("/api/sistema-admin/reportes-fallos/{$this->uuid}/captura") : null,
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'nombre_comercial' => $this->tenant->nombre_comercial,
            ] : null),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
