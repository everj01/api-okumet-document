<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;

class PreguntaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pregunta' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
