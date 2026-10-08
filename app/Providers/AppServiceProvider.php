<?php

namespace App\Providers;

use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use App\Observers\DocumentoObserver;
use App\Observers\EventoObserver;
use App\Observers\ExpedienteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Expediente::observe(ExpedienteObserver::class);
        Documento::observe(DocumentoObserver::class);
        Evento::observe(EventoObserver::class);
    }
}
