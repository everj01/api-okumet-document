<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaudeUsageCounter extends Model
{
    protected $table = 'claude_usage_counters';

    protected $fillable = ['tenant_id', 'periodo', 'llamadas', 'limite'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
