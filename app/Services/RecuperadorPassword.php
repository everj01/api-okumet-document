<?php

namespace App\Services;

use App\Mail\RecuperarPasswordMail;
use App\Models\RecuperacionPassword;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// Nunca revela si un correo existe: el controlador responde igual pase lo que pase aquí adentro.
class RecuperadorPassword
{
    private const MINUTOS_VIGENCIA = 60;

    private const SEGUNDOS_ENTRE_SOLICITUDES = 60;

    public function __construct(private readonly RegistradorAuditoria $auditor) {}

    public function solicitar(string $email): void
    {
        $usuario = User::where('email', $email)->first();

        if (! $usuario || ! $usuario->activo) {
            return;
        }

        $ultimo = $usuario->recuperacionesPassword()->latest()->first();

        if ($ultimo && $ultimo->created_at->diffInSeconds(now()) < self::SEGUNDOS_ENTRE_SOLICITUDES) {
            return;
        }

        // el enlace anterior sin usar queda inválido: solo el último enviado debe servir.
        $usuario->recuperacionesPassword()->whereNull('usado_en')->update(['usado_en' => now()]);

        $tokenCrudo = Str::random(64);

        $usuario->recuperacionesPassword()->create([
            'token' => hash('sha256', $tokenCrudo),
            'expira_en' => now()->addMinutes(self::MINUTOS_VIGENCIA),
        ]);

        $url = sprintf(
            '%s/restablecer-password?token=%s&email=%s',
            rtrim(config('app.frontend_url'), '/'),
            $tokenCrudo,
            urlencode($usuario->email),
        );

        Mail::to($usuario->email)->send(new RecuperarPasswordMail($usuario, $url, self::MINUTOS_VIGENCIA));

        $this->auditor->registrar(
            'password_olvidada',
            'auth',
            'Solicitó un enlace para restablecer su contraseña.',
            actorNombre: $usuario->name,
            actorEmail: $usuario->email,
            usuarioId: $usuario->id,
            tenantId: $usuario->tenant_id,
        );
    }

    public function tokenValido(string $email, string $token): bool
    {
        return $this->buscarVigente($email, $token) !== null;
    }

    // true si se pudo restablecer; false si el enlace no es válido/ya expiró/ya se usó.
    public function restablecer(string $email, string $token, string $password): bool
    {
        $registro = $this->buscarVigente($email, $token);

        if (! $registro) {
            return false;
        }

        $registro->update(['usado_en' => now()]);

        $usuario = $registro->user;
        $usuario->update(['password' => $password]);
        // cierra cualquier sesión abierta en otros dispositivos: quien cambió la clave es quien debe decidir dónde entrar de nuevo.
        $usuario->tokens()->delete();

        $this->auditor->registrar(
            'password_restablecida',
            'auth',
            'Restableció su contraseña mediante el enlace de recuperación.',
            actorNombre: $usuario->name,
            actorEmail: $usuario->email,
            usuarioId: $usuario->id,
            tenantId: $usuario->tenant_id,
        );

        return true;
    }

    private function buscarVigente(string $email, string $token): ?RecuperacionPassword
    {
        $usuario = User::where('email', $email)->first();

        if (! $usuario) {
            return null;
        }

        return $usuario->recuperacionesPassword()
            ->vigentes()
            ->where('token', hash('sha256', $token))
            ->latest()
            ->first();
    }
}
