<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasUuid;

    protected $table = 'notificaciones';

    protected $fillable = ['usuario_id', 'tipo', 'titulo', 'mensaje', 'data', 'leida_en'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'leida_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function leida(): bool
    {
        return $this->leida_en !== null;
    }

    public function scopeNoLeidas(Builder $query): void
    {
        $query->whereNull('leida_en');
    }
}
