<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReporteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'modulo' => $this->modulo,
            'estado' => $this->estado,
            'total_filas' => $this->total_filas,
            'error_mensaje' => $this->error_mensaje,
            'creado_en' => $this->created_at?->toDateTimeString(),
            'completado_en' => $this->completado_en?->toDateTimeString(),
        ];
    }
}
