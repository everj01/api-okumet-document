<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

// El acceso a un documento es el acceso a su expediente: no hay permisos propios del documento.
class DocumentoPolicy
{
    public function __construct(private readonly ExpedientePolicy $expedientes) {}

    public function view(User $usuario, Documento $documento): bool
    {
        return $this->expedientes->view($usuario, $documento->expediente);
    }

    public function delete(User $usuario, Documento $documento): bool
    {
        return $this->expedientes->view($usuario, $documento->expediente);
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
}
