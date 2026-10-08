<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarCodigoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'size:6'],
        ];
    }
}
