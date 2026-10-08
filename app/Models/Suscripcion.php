<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Solo tablas por ahora: sin lógica de negocio ni endpoints hasta que se defina el uso real.
class Suscripcion extends Model
{
    protected $table = 'suscripciones';

    protected $fillable = ['tenant_id', 'plan_id', 'estado', 'inicia_en', 'termina_en'];

    protected function casts(): array
    {
        return [
            'inicia_en' => 'date',
            'termina_en' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
