<?php

namespace App\Observers\Auditoria;

use Illuminate\Database\Eloquent\Model;

class DocumentoAuditoriaObserver extends AuditoriaObserverBase
{
    protected function modulo(): string
    {
        return 'documentos';
    }

    protected function etiqueta(Model $modelo): string
    {
        return "el documento \"{$modelo->nombre}\"";
    }
}
