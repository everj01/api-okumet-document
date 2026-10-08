<?php

namespace App\Services;

use App\Models\AccesoLog;
use App\Models\User;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\Request;

class RegistradorAcceso
{
    // De AccesoLog::evento al accion del log de auditoría unificado: solo estos tres se reportan ahí
    // (verificacion_pendiente_mostrada y codigo_reenviado quedan solo en accesos_log, no son auditables de cara al super admin).
    private const EVENTO_A_ACCION = [
        AccesoLog::LOGIN_EXITOSO => 'login',
        AccesoLog::LOGIN_FALLIDO => 'login_fallido',
        AccesoLog::LOGOUT => 'logout',
    ];

    public function __construct(
        private readonly AnalizadorUserAgent $analizadorUserAgent,
        private readonly GeolocalizadorIp $geolocalizador,
        private readonly RegistradorAuditoria $auditor,
    ) {}

    public function registrar(Request $request, string $evento, ?User $usuario = null, ?string $emailIntentado = null): void
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $geo = $this->geolocalizador->localizar($ip);

        AccesoLog::create([
            'user_id' => $usuario?->id,
            'tenant_id' => $usuario?->tenant_id,
            'email' => $emailIntentado ?? $usuario?->email,
            'evento' => $evento,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'navegador' => $this->analizadorUserAgent->navegador($userAgent),
            'sistema_operativo' => $this->analizadorUserAgent->sistemaOperativo($userAgent),
            'pais' => $geo['pais'],
            'ciudad' => $geo['ciudad'],
        ]);

        $accion = self::EVENTO_A_ACCION[$evento] ?? null;

        if ($accion === null) {
            return;
        }

        $this->auditor->registrar(
            $accion,
            'auth',
            match ($accion) {
                'login' => 'Inició sesión.',
                'login_fallido' => 'Intento de inicio de sesión fallido.',
                default => 'Cerró sesión.',
            },
            actorNombre: $usuario?->name,
            actorEmail: $emailIntentado ?? $usuario?->email,
            usuarioId: $usuario?->id,
            tenantId: $usuario?->tenant_id,
        );
    }
}
