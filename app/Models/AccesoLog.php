<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccesoLog extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'creado_en';

    public const LOGIN_EXITOSO = 'login_exitoso';

    public const LOGIN_FALLIDO = 'login_fallido';

    public const LOGOUT = 'logout';

    public const VERIFICACION_PENDIENTE_MOSTRADA = 'verificacion_pendiente_mostrada';

    public const CODIGO_REENVIADO = 'codigo_reenviado';

    protected $table = 'accesos_log';

    protected $fillable = [
        'user_id', 'tenant_id', 'email', 'evento',
        'ip', 'user_agent', 'navegador', 'sistema_operativo', 'pais', 'ciudad',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
