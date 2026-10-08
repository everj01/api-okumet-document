<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;

class ExtraerPreviaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.mimetypes' => 'El archivo debe ser un PDF.',
            'archivo.max' => 'El archivo no puede pesar más de 20 MB.',
        ];
    }
}
