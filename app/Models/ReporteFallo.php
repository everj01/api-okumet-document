<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteFallo extends Model
{
    use BelongsToTenant, HasUuid;

    public const MOTIVOS = [
        'error_sistema',
        'carga_guardado',
        'documentos_archivos',
        'ia_datos',
        'acceso_permisos',
        'informacion_incorrecta',
        'sugerencia',
        'otro',
    ];

    public const ESTADOS = ['pendiente', 'resuelto'];

    protected $table = 'reportes_fallos';

    protected $fillable = [
        'usuario_id', 'motivo', 'comentario', 'ruta', 'captura_path',
        'estado', 'respuesta', 'resuelto_en',
    ];

    protected function casts(): array
    {
        return [
            'resuelto_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resuelto(): bool
    {
        return $this->estado === 'resuelto';
    }
}
