<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'correos_cc' => ['present', 'array'],
            'correos_cc.*' => ['email', 'max:150'],
        ];
    }
}
