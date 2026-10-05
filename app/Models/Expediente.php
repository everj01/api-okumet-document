<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    use BelongsToTenant, HasUuid;

    public const ESTADOS = ['abierto', 'en_tramite', 'archivado', 'cerrado'];

    protected $fillable = [
        'codigo',
        'titulo',
        'cliente_id',
        'abogado_id',
        'materia',
        'juzgado',
        'estado',
        'fecha_inicio',
        'fecha_cierre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_cierre' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function abogado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abogado_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderByDesc('fecha')->orderByDesc('id');
    }

    public function documentos(): HasMany
    {
        // Se excluye 'texto' (el PDF extraído, puede pesar varios MB): nunca se usa fuera del servidor.
        return $this->hasMany(Documento::class)
            ->select(['id', 'expediente_id', 'nombre', 'ruta', 'tamano', 'paginas', 'resumen', 'subido_por', 'created_at', 'updated_at'])
            ->latest();
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class)->orderBy('inicio');
    }

    public function scopeBuscar(Builder $query, ?string $texto): void
    {
        $query->when($texto, function (Builder $q) use ($texto) {
            $q->where(function (Builder $sub) use ($texto) {
                $sub->where('codigo', 'like', "%{$texto}%")
                    ->orWhere('titulo', 'like', "%{$texto}%")
                    ->orWhere('materia', 'like', "%{$texto}%")
                    ->orWhereHas('cliente', fn (Builder $cliente) => $cliente->where('nombre', 'like', "%{$texto}%"));
            });
        });
    }

    // Un abogado solo ve los expedientes a su cargo; admin y asistente, todos.
    public function scopeVisiblePara(Builder $query, ?User $usuario): void
    {
        $query->when(
            $usuario?->tieneRol(Rol::ABOGADO),
            fn (Builder $q) => $q->where('abogado_id', $usuario->id)
        );
    }

    public function scopeAbiertos(Builder $query): void
    {
        $query->whereIn('estado', ['abierto', 'en_tramite']);
    }
}
