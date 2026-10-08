<?php

namespace App\Observers\Auditoria;

use Illuminate\Database\Eloquent\Model;

class ExpedienteAuditoriaObserver extends AuditoriaObserverBase
{
    protected function modulo(): string
    {
        return 'expedientes';
    }

    protected function etiqueta(Model $modelo): string
    {
        return "el expediente {$modelo->codigo}";
    }
}
