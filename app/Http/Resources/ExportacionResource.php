<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExportacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'estado' => $this->estado,
            'total_expedientes' => $this->total_expedientes,
            'error_mensaje' => $this->error_mensaje,
            'creado_en' => $this->created_at?->toDateTimeString(),
            'completado_en' => $this->completado_en?->toDateTimeString(),
        ];
    }
}
