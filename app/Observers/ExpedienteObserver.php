<?php

namespace App\Observers;

use App\Models\Expediente;

class ExpedienteObserver
{
    public function created(Expediente $expediente): void
    {
        $expediente->movimientos()->create([
            'fecha' => $expediente->fecha_inicio,
            'titulo' => 'Expediente creado',
        ]);
    }

    public function updated(Expediente $expediente): void
    {
        if (! $expediente->isDirty('estado')) {
            return;
        }

        $anterior = Expediente::estadoLegible($expediente->getOriginal('estado'));
        $nuevo = Expediente::estadoLegible($expediente->estado);

        $expediente->movimientos()->create([
            'fecha' => now(),
            'titulo' => "Estado cambiado: {$anterior} → {$nuevo}",
        ]);
    }
}
