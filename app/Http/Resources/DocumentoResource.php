<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'expediente_id' => $this->expediente_id,
            'nombre' => $this->nombre,
            'tamano' => $this->tamano,
            'paginas' => $this->paginas,
            'resumen' => $this->resumen,
            'expediente' => $this->whenLoaded('expediente', fn () => [
                
                'id' => $this->expediente->id,
                'uuid' => $this->expediente->uuid,
                'codigo' => $this->expediente->codigo,
                'titulo' => $this->expediente->titulo,
            ]),
            'subido_por' => $this->whenLoaded('subidoPor', fn () => $this->subidoPor?->name),
            'etiquetas' => EtiquetaResource::collection($this->whenLoaded('etiquetas')),
            'consultas' => ConsultaResource::collection($this->whenLoaded('consultas')),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
