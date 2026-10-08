<?php

namespace App\Services;

use App\Models\AccesoLog;
use App\Models\User;
use Illuminate\Http\Request;

class RegistradorAcceso
{
    public function __construct(
        private readonly AnalizadorUserAgent $analizadorUserAgent,
        private readonly GeolocalizadorIp $geolocalizador,
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
    }
}
