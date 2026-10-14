<?php

namespace App\Http\Requests\Documento;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentosSinExpedienteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'archivos' => ['required', 'array', 'min:1', 'max:20'],
            'archivos.*' => ['file', 'mimetypes:application/pdf', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivos.*.mimetypes' => 'Cada archivo debe ser un PDF.',
            'archivos.*.max' => 'Ningún archivo puede pesar más de 20 MB.',
        ];
    }
}
