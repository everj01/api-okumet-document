<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpedienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'materia' => $this->materia,
            'juzgado' => $this->juzgado,
            'estado' => $this->estado,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_cierre' => $this->fecha_cierre?->toDateString(),
            'descripcion' => $this->descripcion,
            'cliente_id' => $this->cliente_id,
            'abogado_id' => $this->abogado_id,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'nombre_comercial' => $this->tenant->nombre_comercial,
            ]),
            'cliente' => $this->whenLoaded('cliente', fn () => [
                'id' => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'numero_documento' => $this->cliente->numero_documento,
                'activo' => (int) $this->cliente->activo,
            ]),
            'abogado' => $this->whenLoaded('abogado', fn () => [
                'id' => $this->abogado?->id,
                'name' => $this->abogado?->name,
            ]),
            'documentos_count' => $this->whenCounted('documentos'),
            'movimientos' => MovimientoResource::collection($this->whenLoaded('movimientos')),
            'documentos' => DocumentoResource::collection($this->whenLoaded('documentos')),
            'eventos' => EventoResource::collection($this->whenLoaded('eventos')),
            'creado_en' => $this->created_at?->toDateTimeString(),
        ];
    }
}
