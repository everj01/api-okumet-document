<?php

namespace App\Http\Requests\Exportacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearExportacionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'expediente_ids' => ['required', 'array', 'min:1', 'max:50'],
            // Rule::exists consulta la tabla directo, sin el global scope de tenant: hay que acotarlo a mano.
            'expediente_ids.*' => [
                'integer',
                Rule::exists('expedientes', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
        ];
    }
}
