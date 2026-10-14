<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarExpedienteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Rule::exists es una consulta cruda (no pasa por el global scope de Eloquent), así que el
            // tenant se filtra explícitamente para no validar como "existe" un expediente de otro estudio.
            'expediente_id' => [
                'required',
                'integer',
                Rule::exists('expedientes', 'id')->where('tenant_id', $this->user()?->tenant_id),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['expediente_id' => 'expediente'];
    }
}
