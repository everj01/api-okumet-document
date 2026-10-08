<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    public const CREATED_AT = 'creado_en';

    protected $table = 'auditorias';

    protected $fillable = [
        'usuario_id', 'tenant_id', 'actor_nombre', 'actor_email',
        'accion', 'modulo', 'descripcion', 'ip',
    ];

    protected function casts(): array
    {
        return ['creado_en' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
