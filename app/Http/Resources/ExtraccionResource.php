<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtraccionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'documento_id' => $this->documento_id,
            // Objeto, no lista: un campo no detectado viene ausente en vez de null.
            'campos' => (object) ($this->campos ?? []),
            'partes' => $this->partes ?? [],
            'fechas_clave' => $this->fechas_clave ?? [],
            'creado_en' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
