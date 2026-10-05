<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'razon_social' => ['nullable', 'string', 'max:180'],
            'ruc' => ['nullable', 'string', 'max:20', Rule::unique('tenants', 'ruc')->ignore($this->user()->tenant_id)],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_comercial' => 'nombre comercial',
            'razon_social' => 'razón social',
        ];
    }
}
