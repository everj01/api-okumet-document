<?php

namespace App\Http\Requests\ReporteFallo;

use Illuminate\Foundation\Http\FormRequest;

class ResolverReporteFalloRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'respuesta' => ['required', 'string', 'max:2000'],
        ];
    }
}
