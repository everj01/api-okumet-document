<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SincronizarEtiquetasRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'etiqueta_ids' => ['present', 'array'],
            'etiqueta_ids.*' => ['integer', Rule::exists('etiquetas', 'id')->where('tenant_id', $tenantId)],
        ];
    }
}
