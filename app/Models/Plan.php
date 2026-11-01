<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'limite_llamadas_claude',
        'limite_usuarios',
        'limite_almacenamiento_mb',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }

    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }

    // Único plan con precio 0: define si el corte al vencer es inmediato (Free) o con gracia (pagado).
    public function esGratuito(): bool
    {
        return (float) $this->precio === 0.0;
    }
}
