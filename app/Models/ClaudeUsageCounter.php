<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaudeUsageCounter extends Model
{
    protected $table = 'claude_usage_counters';

    protected $fillable = ['usuario_id', 'periodo', 'llamadas', 'limite'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
