<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoExtraccion extends Model
{
    protected $table = 'documento_extracciones';

    protected $fillable = ['documento_id', 'campos', 'partes', 'fechas_clave'];

    protected function casts(): array
    {
        return [
            'campos' => 'array',
            'partes' => 'array',
            'fechas_clave' => 'array',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }
}
