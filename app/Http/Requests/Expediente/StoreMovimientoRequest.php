<?php

namespace App\Http\Requests\Expediente;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'titulo' => ['required', 'string', 'max:180'],
            'detalle' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
