<?php

use App\Models\Expediente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('expediente.{expedienteId}', function (User $user, int $expedienteId) {
    $expediente = Expediente::find($expedienteId);

    if (! $expediente) {
        return false;
    }

    return $user->tieneRol(Rol::ADMIN, Rol::ASISTENTE)
        || $expediente->abogado_id === $user->id;
});

Broadcast::channel('agenda', function (User $user) {
    return true;
});

// Mismo criterio que Expediente::scopeVisiblePara: admin/asistente ven todo.
Broadcast::channel('expedientes.todos', function (User $user) {
    return $user->tieneRol(Rol::ADMIN, Rol::ASISTENTE);
});

Broadcast::channel('expedientes.abogado.{abogadoId}', function (User $user, int $abogadoId) {
    return $user->tieneRol(Rol::ADMIN, Rol::ASISTENTE) || $user->id === $abogadoId;
});

// Notificaciones y actualizaciones de créditos de IA en tiempo real: estrictamente personal.
Broadcast::channel('usuario.{usuarioId}', function (User $user, int $usuarioId) {
    return $user->id === $usuarioId;
});
