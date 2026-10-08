<?php

namespace App\Observers;

use App\Models\Evento;

class EventoObserver
{
    public function updated(Evento $evento): void
    {
        if (! $evento->isDirty('estado') || $evento->estado !== 'realizado' || ! $evento->expediente_id) {
            return;
        }

        $evento->expediente?->movimientos()->create([
            'fecha' => $evento->inicio->toDateString(),
            'titulo' => $evento->titulo,
        ]);
    }
}
