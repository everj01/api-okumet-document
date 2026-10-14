<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoHistorial extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'creado_en';

    public const SUBIDO_HUERFANO = 'subido_huerfano';

    public const ASIGNADO_A_EXPEDIENTE = 'asignado_a_expediente';

    protected $table = 'documento_historial';

    protected $fillable = ['documento_id', 'evento', 'expediente_id', 'usuario_id'];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
