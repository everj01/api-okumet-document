<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoPagina extends Model
{
    protected $table = 'documento_paginas';

    public $timestamps = false;

    protected $fillable = ['documento_id', 'numero', 'texto'];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }
}
