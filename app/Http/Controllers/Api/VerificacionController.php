<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmarCodigoRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\AccesoLog;
use App\Services\RegistradorAcceso;
use App\Services\VerificadorCorreo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerificacionController extends Controller
{
    private const SEGUNDOS_ENTRE_REENVIOS = 60;

    public function __construct(
        private readonly VerificadorCorreo $verificadorCorreo,
        private readonly RegistradorAcceso $registradorAcceso,
    ) {}

    public function reenviar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $ultimo = $usuario->codigosVerificacion()->latest()->first();

        $segundosTranscurridos = $ultimo ? (int) $ultimo->created_at->diffInSeconds(now()) : null;

        if ($segundosTranscurridos !== null && $segundosTranscurridos < self::SEGUNDOS_ENTRE_REENVIOS) {
            $faltan = self::SEGUNDOS_ENTRE_REENVIOS - $segundosTranscurridos;

            return response()->json([
                'message' => "Espera {$faltan} segundos antes de pedir otro código.",
            ], 429);
        }

        $this->verificadorCorreo->generarYEnviar($usuario);
        $this->registradorAcceso->registrar($request, AccesoLog::CODIGO_REENVIADO, $usuario);

        return response()->json(['message' => 'Te enviamos un nuevo código a tu correo.']);
    }

    public function confirmar(ConfirmarCodigoRequest $request): JsonResponse
    {
        $usuario = $request->user();

        $codigo = $usuario->codigosVerificacion()
            ->vigentes()
            ->where('codigo', mb_strtoupper((string) $request->input('codigo')))
            ->latest()
            ->first();

        if (! $codigo) {
            throw ValidationException::withMessages([
                'codigo' => ['El código no es válido o ya expiró.'],
            ]);
        }

        $codigo->update(['usado_en' => now()]);
        // email_verified_at no está en $fillable a propósito (no debe ser editable por un update genérico).
        $usuario->forceFill(['email_verified_at' => now()])->save();

        return response()->json(['data' => new UsuarioResource($usuario)]);
    }
}
