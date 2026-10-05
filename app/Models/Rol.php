<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public const ADMIN = 'admin';

    public const ABOGADO = 'abogado';

    public const ASISTENTE = 'asistente';

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
