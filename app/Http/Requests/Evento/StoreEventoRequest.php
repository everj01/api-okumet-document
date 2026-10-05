<?php

namespace App\Http\Requests\Evento;

use App\Models\Evento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventoRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            // where tenant_id: un evento nunca debe poder enlazar un expediente o responsable de otro tenant.
            'expediente_id' => ['nullable', 'integer', Rule::exists('expedientes', 'id')->where('tenant_id', $tenantId)],
            'responsable_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'tipo' => ['required', Rule::in(Evento::TIPOS)],
            'titulo' => ['required', 'string', 'max:180'],
            'inicio' => ['required', 'date'],
            'fin' => ['nullable', 'date', 'after:inicio'],
            'lugar' => ['nullable', 'string', 'max:180'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'recordatorio_dias' => ['required', 'integer', 'min:0', 'max:60'],
            'estado' => ['required', Rule::in(Evento::ESTADOS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'expediente_id' => 'expediente',
            'responsable_id' => 'responsable',
            'recordatorio_dias' => 'recordatorio',
        ];
    }
}
