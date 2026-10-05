<?php

namespace App\Policies;

use App\Models\Expediente;
use App\Models\Rol;
use App\Models\User;

// Un abogado solo ve sus expedientes; admin y asistente ven todo el estudio.
class ExpedientePolicy
{
    public function view(User $usuario, Expediente $expediente): bool
    {
        return $this->tieneAcceso($usuario, $expediente);
    }

    public function update(User $usuario, Expediente $expediente): bool
    {
        return $this->tieneAcceso($usuario, $expediente);
    }

    public function delete(User $usuario, Expediente $expediente): bool
    {
        return $this->tieneAcceso($usuario, $expediente);
    }

    private function tieneAcceso(User $usuario, Expediente $expediente): bool
    {
        if (! $usuario->tieneRol(Rol::ABOGADO)) {
            return true;
        }

        return $expediente->abogado_id === $usuario->id;
    }
}
