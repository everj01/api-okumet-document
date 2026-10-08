<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Solo tablas por ahora: sin lógica de negocio ni endpoints hasta que se defina el uso real.
class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'limite_llamadas_claude_admin',
        'limite_llamadas_claude_usuario',
        'limite_usuarios',
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
}
