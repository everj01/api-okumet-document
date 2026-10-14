<?php

namespace App\Http\Requests\ReporteFallo;

use App\Models\ReporteFallo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReporteFalloRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'motivo' => ['required', Rule::in(ReporteFallo::MOTIVOS)],
            'comentario' => ['nullable', 'string', 'max:2000'],
            'ruta' => ['nullable', 'string', 'max:255'],
            'captura' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Selecciona un motivo para el reporte.',
            'motivo.in' => 'El motivo seleccionado no es válido.',
            'captura.image' => 'La captura debe ser una imagen.',
            'captura.max' => 'La captura no puede pesar más de 5 MB.',
        ];
    }
}
