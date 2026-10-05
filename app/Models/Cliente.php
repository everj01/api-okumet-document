<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'tipo_persona',
        'tipo_documento',
        'numero_documento',
        'nombre',
        'email',
        'telefono',
        'direccion',
        'notas',
        'activo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function scopeBuscar(Builder $query, ?string $texto): void
    {
        $query->when($texto, function (Builder $q) use ($texto) {
            $q->where(function (Builder $sub) use ($texto) {
                $sub->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('numero_documento', 'like', "%{$texto}%")
                    ->orWhere('email', 'like', "%{$texto}%");
            });
        });
    }
}
