<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exportacion extends Model
{
    use BelongsToTenant, HasUuid;

    protected $table = 'exportaciones';

    public const ESTADOS = ['pendiente', 'procesando', 'completado', 'fallido'];

    protected $fillable = [
        'solicitado_por',
        'estado',
        'filtros',
        'total_expedientes',
        'archivo_path',
        'error_mensaje',
        'completado_en',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
            'completado_en' => 'datetime',
        ];
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function completada(): bool
    {
        return $this->estado === 'completado';
    }
}
