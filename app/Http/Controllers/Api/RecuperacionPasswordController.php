<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RestablecerPasswordRequest;
use App\Http\Requests\Auth\SolicitarRecuperacionRequest;
use App\Services\RecuperadorPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RecuperacionPasswordController extends Controller
{
    private const MENSAJE_GENERICO = 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.';

    public function __construct(private readonly RecuperadorPassword $recuperador) {}

    // Responde igual exista o no la cuenta: no es un bug, es la defensa contra enumeración de usuarios.
    public function solicitar(SolicitarRecuperacionRequest $request): JsonResponse
    {
        $this->recuperador->solicitar($request->string('email')->toString());

        return response()->json(['message' => self::MENSAJE_GENERICO]);
    }

    public function restablecer(RestablecerPasswordRequest $request): JsonResponse
    {
        $restablecido = $this->recuperador->restablecer(
            $request->string('email')->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        if (! $restablecido) {
            throw ValidationException::withMessages([
                'token' => ['Este enlace ya no es válido. Pide uno nuevo.'],
            ]);
        }

        return response()->json(['message' => 'Tu contraseña se actualizó. Ya puedes iniciar sesión.']);
    }
}
