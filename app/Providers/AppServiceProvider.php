<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\User;
use App\Observers\Auditoria\ClienteAuditoriaObserver;
use App\Observers\Auditoria\DocumentoAuditoriaObserver;
use App\Observers\Auditoria\EventoAuditoriaObserver;
use App\Observers\Auditoria\ExpedienteAuditoriaObserver;
use App\Observers\Auditoria\UsuarioAuditoriaObserver;
use App\Observers\DocumentoObserver;
use App\Observers\EventoObserver;
use App\Observers\ExpedienteObserver;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
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

        // Aparte de los de negocio de arriba: solo escriben en el log de auditoría, no tocan nada más.
        Cliente::observe(ClienteAuditoriaObserver::class);
        Expediente::observe(ExpedienteAuditoriaObserver::class);
        Documento::observe(DocumentoAuditoriaObserver::class);
        Evento::observe(EventoAuditoriaObserver::class);
        User::observe(UsuarioAuditoriaObserver::class);

        // MAIL_BCC: copia oculta en todo correo saliente, para confirmar que el envío real funciona.
        // Laravel no tiene un alwaysBcc() nativo (solo alwaysFrom/alwaysTo/alwaysReplyTo), así que se
        // engancha al evento que dispara justo antes de enviar cualquier mensaje, sea Mailable o Mail::raw.
        if (filled(config('mail.bcc'))) {
            Event::listen(MessageSending::class, function (MessageSending $event) {
                $event->message->addBcc(config('mail.bcc'));
            });
        }
    }
}
