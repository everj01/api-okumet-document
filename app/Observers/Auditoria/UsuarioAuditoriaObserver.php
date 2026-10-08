<?php

namespace App\Observers\Auditoria;

use Illuminate\Database\Eloquent\Model;

class UsuarioAuditoriaObserver extends AuditoriaObserverBase
{
    protected function modulo(): string
    {
        return 'usuarios';
    }

    protected function etiqueta(Model $modelo): string
    {
        return "al usuario {$modelo->name}";
    }
}
