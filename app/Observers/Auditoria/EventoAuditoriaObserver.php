<?php

namespace App\Observers\Auditoria;

use Illuminate\Database\Eloquent\Model;

class EventoAuditoriaObserver extends AuditoriaObserverBase
{
    protected function modulo(): string
    {
        return 'eventos';
    }

    protected function etiqueta(Model $modelo): string
    {
        return "el evento \"{$modelo->titulo}\"";
    }
}
