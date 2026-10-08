<?php

namespace App\Http\Requests\Reporte;

use App\Services\Reportes\ColumnasReporte;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearReporteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'modulo' => ['required', 'string', Rule::in(ColumnasReporte::MODULOS)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'columnas' => ['required', 'array', 'min:1'],
        ];
    }

    // Las claves válidas de "columnas" dependen de "modulo", así que se validan después de que
    // las reglas básicas ya confirmaron que "modulo" es uno de los permitidos.
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $modulo = $this->input('modulo');
            $columnas = $this->input('columnas');

            if (! is_string($modulo) || ! in_array($modulo, ColumnasReporte::MODULOS, true) || ! is_array($columnas)) {
                return;
            }

            $validas = ColumnasReporte::claves($modulo);

            foreach ($columnas as $columna) {
                if (! in_array($columna, $validas, true)) {
                    $validator->errors()->add('columnas', "La columna \"{$columna}\" no existe para el módulo \"{$modulo}\".");
                }
            }
        });
    }
}
