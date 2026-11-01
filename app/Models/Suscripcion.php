<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends Model
{
    // Días de gracia tras pasar a 'vencida' antes de bloquear el acceso (solo aplica a planes pagados;
    // el plan Free no tiene gracia, corta directo al pasar su termina_en).
    public const DIAS_GRACIA = 5;

    protected $table = 'suscripciones';

    protected $fillable = ['tenant_id', 'plan_id', 'estado', 'inicia_en', 'termina_en', 'vencida_en'];

    protected function casts(): array
    {
        return [
            'inicia_en' => 'date',
            'termina_en' => 'date',
            'vencida_en' => 'date',
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

    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', 'activa');
    }

    // Transición manual (sin pasarela todavía): la dispara un SuperAdmin cuando corresponde.
    public function marcarVencida(): void
    {
        if ($this->estado !== 'vencida') {
            $this->update(['estado' => 'vencida', 'vencida_en' => now()->toDateString()]);
        }
    }

    public function estaBloqueada(): bool
    {
        if ($this->estado === 'cancelada') {
            return true;
        }

        if ($this->estado === 'vencida') {
            $desde = $this->vencida_en ?? $this->termina_en;

            return $desde !== null && $desde->copy()->addDays(self::DIAS_GRACIA)->isPast();
        }

        // Plan gratuito sin pasarela: el corte a los 30 días es inmediato, sin gracia.
        if ($this->termina_en !== null && $this->plan?->esGratuito() && $this->termina_en->isPast()) {
            return true;
        }

        return false;
    }
}
