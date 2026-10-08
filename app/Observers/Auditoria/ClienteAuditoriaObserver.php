<?php

namespace App\Observers\Auditoria;

use Illuminate\Database\Eloquent\Model;

class ClienteAuditoriaObserver extends AuditoriaObserverBase
{
    protected function modulo(): string
    {
        return 'clientes';
    }

    protected function etiqueta(Model $modelo): string
    {
        return "al cliente \"{$modelo->nombre}\"";
    }
}
