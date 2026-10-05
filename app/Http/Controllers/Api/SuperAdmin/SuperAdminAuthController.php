<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperAdminAuthController extends Controller
{
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
            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        return response()->json([
            'token' => $admin->createToken('okd-sistema-admin')->plainTextToken,
            'usuario' => ['id' => $admin->id, 'email' => $admin->email, 'name' => $admin->name],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
