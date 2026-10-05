<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/* Soporte técnico, no pertenece a ningún tenant. Usa el mismo guard sanctum que User (Sanctum soporta tokens de
cualquier modelo), así que $request->user() puede devolver un User o un SuperAdmin según el token: TenantScope solo
filtra cuando es un User, y EnsureSuperAdmin exige que sea un SuperAdmin. */
class SuperAdmin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
}
