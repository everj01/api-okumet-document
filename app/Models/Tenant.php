<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'ruc',
        'logo_path',
        'direccion',
        'telefono',
        'email',
        'activo',
        'configuracion',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'configuracion' => 'array',
        ];
    }

    // Correos en copia para notificaciones (agenda, etc), configurables por el admin del tenant.
    public function correosCc(): array
    {
        return $this->configuracion['correos_cc'] ?? [];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }
}
