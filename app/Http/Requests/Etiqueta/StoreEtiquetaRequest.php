<?php

namespace App\Http\Requests\Etiqueta;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtiquetaRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:60',
                Rule::unique('etiquetas', 'nombre')->where('tenant_id', $tenantId)->ignore($this->route('etiqueta')?->id),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'color.regex' => 'El color debe ser un valor hexadecimal, ej. #F5D6C6.',
        ];
    }
}
