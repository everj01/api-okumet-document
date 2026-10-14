<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Documento extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = ['expediente_id', 'nombre', 'ruta', 'tamano', 'paginas', 'texto', 'resumen', 'subido_por'];

    // El texto completo solo se usa dentro del servidor, nunca sale en el JSON
    protected $hidden = ['texto'];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function paginasTexto(): HasMany
    {
        return $this->hasMany(DocumentoPagina::class)->orderBy('numero');
    }

    public function extraccion(): HasOne
    {
        return $this->hasOne(DocumentoExtraccion::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class)->latest();
    }

    public function etiquetas(): BelongsToMany
    {
        return $this->belongsToMany(Etiqueta::class);
    }

    public function historial(): HasMany
    {
        return $this->hasMany(DocumentoHistorial::class)->latest('creado_en');
    }

    // Con expediente: visible si lo es su expediente. Huérfano (expediente_id null): solo quien lo
    // subió, o un admin (confirmado con el usuario: asistente y otros abogados no lo ven hasta que
    // se asigne a un expediente).
    public function scopeVisiblePara(Builder $query, ?User $usuario): void
    {
        $query->where(function (Builder $q) use ($usuario) {
            $q->whereHas('expediente', fn (Builder $expediente) => $expediente->visiblePara($usuario))
                ->orWhere(function (Builder $huerfano) use ($usuario) {
                    $huerfano->whereNull('expediente_id')
                        ->when(
                            ! $usuario?->tieneRol(Rol::ADMIN),
                            fn (Builder $q) => $q->where('subido_por', $usuario?->id)
                        );
                });
        });
    }

    public function scopeBuscar(Builder $query, ?string $texto): void
    {
        $query->when($texto, function (Builder $q) use ($texto) {
            $q->where(function (Builder $sub) use ($texto) {
                $sub->where('nombre', 'like', "%{$texto}%")
                    ->orWhereFullText(['nombre', 'texto'], $texto);
            });
        });
    }
}
