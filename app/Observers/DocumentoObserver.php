<?php

namespace App\Observers;

use App\Models\Documento;

class DocumentoObserver
{
    public function created(Documento $documento): void
    {
        $documento->expediente?->movimientos()->create([
            'fecha' => now(),
            'titulo' => "Documento subido: {$documento->nombre}",
        ]);
    }
}
