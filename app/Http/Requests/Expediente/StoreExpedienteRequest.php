<?php

namespace App\Http\Requests\Expediente;

use App\Models\Expediente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpedienteRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'codigo' => [
                'required',
                'string',
                'max:60',
                Rule::unique('expedientes', 'codigo')->where('tenant_id', $tenantId)->ignore($this->route('expediente')?->id),
            ],
            'titulo' => ['required', 'string', 'max:180'],
            // where tenant_id: un expediente nunca debe poder enlazar un cliente o abogado de otro tenant.
            'cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('tenant_id', $tenantId)],
            'abogado_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'materia' => ['nullable', 'string', 'max:100'],
            'juzgado' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(Expediente::ESTADOS)],
            'fecha_inicio' => ['required', 'date'],
            'anio' => ['nullable', 'integer', 'digits:4'],
            'fecha_cierre' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'cliente_id' => 'cliente',
            'abogado_id' => 'abogado',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_cierre' => 'fecha de cierre',
        ];
    }
}
