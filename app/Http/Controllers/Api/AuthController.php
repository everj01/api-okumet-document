<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistroRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\AccesoLog;
use App\Models\Rol;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RegistradorAcceso;
use App\Services\VerificadorCorreo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegistradorAcceso $registradorAcceso,
        private readonly VerificadorCorreo $verificadorCorreo,
    ) {}

    // Alta pública: crea un tenant nuevo con quien se registra como su único administrador.
    public function registro(RegistroRequest $request): JsonResponse
    {
        $admin = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'nombre_comercial' => $request->nombre_comercial,
                'ruc' => $request->ruc,
            ]);

            $admin = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'rol_id' => Rol::where('nombre', Rol::ADMIN)->value('id'),
                'activo' => true,
            ]);
            // Asignación directa: BelongsToTenant no puede autoasignarlo sin usuario autenticado en este request.
            $admin->tenant_id = $tenant->id;
            $admin->save();

            return $admin;
        });

        $this->verificadorCorreo->generarYEnviar($admin);
        $this->registradorAcceso->registrar($request, AccesoLog::VERIFICACION_PENDIENTE_MOSTRADA, $admin);

        return response()->json([
            'token' => $admin->createToken('okd-web')->plainTextToken,
            'usuario' => new UsuarioResource($admin->load(['rol', 'tenant'])),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = User::withoutGlobalScopes()->with(['rol', 'tenant'])->where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            $this->registradorAcceso->registrar($request, AccesoLog::LOGIN_FALLIDO, $usuario, $request->email);

            throw ValidationException::withMessages([
                'email' => ['El correo o la contraseña no son correctos.'],
            ]);
        }

        if (! $usuario->activo) {
            $this->registradorAcceso->registrar($request, AccesoLog::LOGIN_FALLIDO, $usuario);

            throw ValidationException::withMessages([
                'email' => ['Tu usuario está desactivado. Comunícate con el administrador.'],
            ]);
        }

        $this->registradorAcceso->registrar($request, AccesoLog::LOGIN_EXITOSO, $usuario);

        if (! $usuario->emailVerificado()) {
            $this->registradorAcceso->registrar($request, AccesoLog::VERIFICACION_PENDIENTE_MOSTRADA, $usuario);
        }

        return response()->json([
            'token' => $usuario->createToken('okd-web')->plainTextToken,
            'usuario' => new UsuarioResource($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->registradorAcceso->registrar($request, AccesoLog::LOGOUT, $request->user());

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function yo(Request $request): JsonResponse
    {
        return response()->json(['usuario' => new UsuarioResource($request->user()->load(['rol', 'tenant']))]);
    }

    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $usuario = $request->user();

        if (! Hash::check($request->password_actual, $usuario->password)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $usuario->update(['password' => $request->password]);

        return response()->json(['message' => 'Contraseña actualizada.']);
    }
}
