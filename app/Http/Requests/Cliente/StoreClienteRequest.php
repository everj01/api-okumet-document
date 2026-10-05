<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
{
    public function rules(): array
    {
        $documentosPermitidos = $this->input('tipo_persona') === 'juridica'
            ? ['RUC']
            : ['DNI', 'RUC', 'CE', 'PASAPORTE'];

        return [
            'tipo_persona' => ['required', Rule::in(['natural', 'juridica'])],
            'tipo_documento' => ['required', Rule::in($documentosPermitidos)],
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'numero_documento')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('cliente')?->id),
            ],
            'nombre' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'numero_documento' => 'número de documento',
            'tipo_persona' => 'tipo de persona',
            'tipo_documento' => 'tipo de documento',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.in' => 'Un cliente con persona jurídica solo puede usar RUC como tipo de documento.',
        ];
    }
}
