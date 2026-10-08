<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    use HasUuid;

    protected $table = 'reportes';

    protected $fillable = [
        'modulo', 'filtros', 'columnas', 'estado',
        'total_filas', 'archivo_path', 'error_mensaje', 'completado_en',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
            'columnas' => 'array',
            'completado_en' => 'datetime',
        ];
    }

    public function completado(): bool
    {
        return $this->estado === 'completado';
    }
}
