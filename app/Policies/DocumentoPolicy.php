<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\Rol;
use App\Models\User;

// El acceso a un documento con expediente es el acceso a su expediente. Un documento huérfano
// (sin expediente todavía) no tiene expediente del cual heredar permiso: solo lo ve quien lo
// subió, o un admin.
class DocumentoPolicy
{
    public function __construct(private readonly ExpedientePolicy $expedientes) {}

    public function view(User $usuario, Documento $documento): bool
    {
        if ($documento->expediente_id === null) {
            return $documento->subido_por === $usuario->id || $usuario->tieneRol(Rol::ADMIN);
        }

        return $this->expedientes->view($usuario, $documento->expediente);
    }

    public function delete(User $usuario, Documento $documento): bool
    {
        return $this->view($usuario, $documento);
    }

    // Resumir, preguntar y extraer mandan el documento a Claude: mismo acceso que leerlo.
    public function analizar(User $usuario, Documento $documento): bool
    {
        return $this->view($usuario, $documento);
    }

    // Etiquetar es editar el documento: mismo acceso que verlo.
    public function etiquetar(User $usuario, Documento $documento): bool
    {
        return $this->view($usuario, $documento);
    }

    // Convertir un huérfano en un documento de expediente: mismo criterio que verlo.
    public function asignarExpediente(User $usuario, Documento $documento): bool
    {
        return $documento->expediente_id === null && $this->view($usuario, $documento);
    }
}
