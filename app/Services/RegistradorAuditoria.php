<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Único punto que escribe en el log de auditoría unificado. Para CRUD, el actor se toma del
// usuario autenticado de la request (siempre hay uno: estas acciones solo ocurren dentro de un
// request autenticado). Para login/logout hay que pasar el actor explícito porque en ese punto
// todavía no hay sesión iniciada (login fallido no tiene usuario; login exitoso aún no llamó a Auth::login).
class RegistradorAuditoria
{
    public function registrar(
        string $accion,
        string $modulo,
        string $descripcion,
        ?string $actorNombre = null,
        ?string $actorEmail = null,
        ?int $usuarioId = null,
        ?int $tenantId = null,
    ): void {
        $actor = Auth::user();

        Auditoria::create([
            'usuario_id' => $usuarioId ?? ($actor instanceof User ? $actor->id : null),
            'tenant_id' => $tenantId ?? ($actor instanceof User ? $actor->tenant_id : null),
            'actor_nombre' => $actorNombre ?? $actor?->name,
            'actor_email' => $actorEmail ?? $actor?->email,
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
            'ip' => request()?->ip(),
        ]);
    }
}
