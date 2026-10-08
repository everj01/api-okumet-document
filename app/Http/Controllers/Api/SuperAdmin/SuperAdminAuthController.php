<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperAdminAuthController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditor) {}

    // Paso previo al login: solo habilita que el frontend muestre el formulario.
    public function clave(Request $request): JsonResponse
    {
        $request->validate(['clave' => ['required', 'string']]);

        if (! hash_equals((string) config('services.super_admin.clave'), $request->input('clave'))) {
            throw ValidationException::withMessages(['clave' => ['Clave incorrecta.']]);
        }

        return response()->json(['message' => 'Clave correcta.']);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = SuperAdmin::where('email', $request->input('email'))->first();

        if (! $admin || ! $admin->activo || ! Hash::check($request->input('password'), $admin->password)) {
            $this->auditor->registrar(
                'login_fallido',
                'auth',
                'Intento de inicio de sesión fallido (soporte técnico).',
                actorEmail: $request->input('email'),
            );

            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        $this->auditor->registrar(
            'login',
            'auth',
            'Inició sesión como soporte técnico.',
            actorNombre: $admin->name,
            actorEmail: $admin->email,
        );

        return response()->json([
            'token' => $admin->createToken('okd-sistema-admin')->plainTextToken,
            'usuario' => ['id' => $admin->id, 'email' => $admin->email, 'name' => $admin->name],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $admin = $request->user();

        $this->auditor->registrar(
            'logout',
            'auth',
            'Cerró sesión de soporte técnico.',
            actorNombre: $admin->name,
            actorEmail: $admin->email,
        );

        $admin->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
