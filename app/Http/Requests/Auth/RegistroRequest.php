<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistroRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'ruc' => ['required', 'string', 'max:20', Rule::unique('tenants', 'ruc')],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_comercial' => 'nombre comercial',
            'ruc' => 'RUC',
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'ruc.unique' => 'Ya existe un negocio registrado con ese RUC.',
        ];
    }
}
