<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    use BelongsToTenant, HasUuid;

    public const TIPOS = [
        'audiencia', 'vencimiento', 'reunion', 'otro',
        'plazo_absolver', 'plazo_apelar', 'actuacion_prueba',
        'sentencia_1_instancia', 'sentencia_2_instancia',
    ];

    public const ESTADOS = ['pendiente', 'realizado', 'cancelado'];

    protected $fillable = [
        'expediente_id',
        'responsable_id',
        'tipo',
        'titulo',
        'inicio',
        'fin',
        'lugar',
        'notas',
        'recordatorio_dias',
        'recordatorio_enviado_en',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'recordatorio_enviado_en' => 'datetime',
        ];
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    // Un evento ligado a un expediente se ve solo si el expediente se ve; los sueltos son agenda del estudio.
    public function scopeVisiblePara(Builder $query, ?User $usuario): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereNull('expediente_id')
            ->orWhereHas('expediente', fn (Builder $expediente) => $expediente->visiblePara($usuario))
        );
    }

    public function scopePendientes(Builder $query): void
    {
        $query->where('estado', 'pendiente');
    }

    public function scopeEntre(Builder $query, ?string $desde, ?string $hasta): void
    {
        $query->when($desde, fn (Builder $q) => $q->where('inicio', '>=', $desde.' 00:00:00'))
            ->when($hasta, fn (Builder $q) => $q->where('inicio', '<=', $hasta.' 23:59:59'));
    }
}
