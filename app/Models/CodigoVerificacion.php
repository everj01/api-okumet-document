<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodigoVerificacion extends Model
{
    protected $table = 'codigos_verificacion';

    protected $fillable = ['user_id', 'codigo', 'expira_en', 'usado_en'];

    protected function casts(): array
    {
        return [
            'expira_en' => 'datetime',
            'usado_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVigentes(Builder $query): void
    {
        $query->whereNull('usado_en')->where('expira_en', '>=', now());
    }

    public function estaVigente(): bool
    {
        return $this->usado_en === null && $this->expira_en->isFuture();
    }
}
