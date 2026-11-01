<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:planes,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'limite_llamadas_claude' => ['required', 'integer', 'min:0'],
            'limite_usuarios' => ['required', 'integer', 'min:1'],
            'limite_almacenamiento_mb' => ['nullable', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'precio' => 'precio',
            'limite_llamadas_claude' => 'límite de llamadas a Claude',
            'limite_usuarios' => 'límite de usuarios',
            'limite_almacenamiento_mb' => 'límite de almacenamiento',
        ];
    }
}
