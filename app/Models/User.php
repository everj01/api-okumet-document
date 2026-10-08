<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToTenant, HasApiTokens, HasFactory, HasUuid, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'rol_id', 'telefono', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'abogado_id');
    }

    public function codigosVerificacion(): HasMany
    {
        return $this->hasMany(CodigoVerificacion::class);
    }

    public function emailVerificado(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol(Rol::ADMIN);
    }

    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }
}
