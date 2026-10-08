<?php

namespace App\Services;

use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class VerificadorCorreo
{
    private const MINUTOS_VIGENCIA = 15;

    // Evita ambiguos (0/O, 1/I) para que el código se pueda transcribir a mano sin dudas.
    private const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generarYEnviar(User $usuario): CodigoVerificacion
    {
        // El código anterior sin usar queda inválido: solo el último enviado debe servir.
        $usuario->codigosVerificacion()->whereNull('usado_en')->update(['usado_en' => now()]);

        $codigo = $usuario->codigosVerificacion()->create([
            'codigo' => $this->generarCodigo(),
            'expira_en' => now()->addMinutes(self::MINUTOS_VIGENCIA),
        ]);

        Mail::to($usuario->email)->send(new CodigoVerificacionMail($codigo));

        return $codigo;
    }

    private function generarCodigo(): string
    {
        return collect(range(1, 6))
            ->map(fn () => self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)])
            ->implode('');
    }
}
