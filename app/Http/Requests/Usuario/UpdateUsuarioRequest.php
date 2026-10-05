<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('usuario')->id)],
            'password' => ['nullable', Password::min(8)],
            'rol_id' => ['required', 'integer', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
            'rol_id' => 'rol',
        ];
    }
}
