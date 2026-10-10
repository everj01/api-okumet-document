<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioMail;
use App\Models\Evento;
use App\Services\Notificador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarRecordatorios extends Command
{
    protected $signature = 'okd:recordatorios';

    protected $description = 'Avisa por correo de las audiencias y vencimientos próximos';

    // Dominios reservados para documentación/pruebas (RFC 2606), nunca reciben correo real.
    private const DOMINIOS_RESERVADOS = ['example.com', 'example.net', 'example.org', 'example.edu', 'test', 'invalid', 'localhost'];

    public function handle(Notificador $notificador): int
    {
        $eventos = Evento::with(['responsable', 'expediente', 'tenant'])
            ->pendientes()
            ->whereNull('recordatorio_enviado_en')
            ->whereNotNull('responsable_id')
            ->where('inicio', '>=', now())
            ->get()
            ->filter(fn (Evento $evento) => now()->diffInDays($evento->inicio, false) <= $evento->recordatorio_dias);

        $enviados = 0;

        foreach ($eventos as $evento) {
            $correo = $evento->responsable?->email;

            if (! $correo || ! $this->esCorreoValido($correo)) {
                $this->warn("Omitido evento #{$evento->id}: correo inválido o de prueba ({$correo}).");

                continue;
            }

            $copias = array_diff($evento->tenant?->correosCc() ?? [], [$correo]);

            try {
                Mail::to($correo)->cc($copias)->send(new RecordatorioMail($evento));

                $evento->update(['recordatorio_enviado_en' => now()]);
                $enviados++;

                // Mismo disparador que el correo: si el correo no se manda (dominio inválido o fallo), tampoco se notifica.
                $notificador->enviar(
                    $evento->responsable,
                    'evento_proximo',
                    'Evento próximo',
                    "Tienes \"{$evento->titulo}\" el {$evento->inicio->format('d/m/Y H:i')}.",
                    ['evento_uuid' => $evento->uuid],
                );
            } catch (Throwable $e) {
                Log::warning("No se pudo enviar recordatorio del evento #{$evento->id} a {$correo}: {$e->getMessage()}");
            }
        }

        $this->info("Recordatorios enviados: {$enviados}");

        $this->notificarVencidos($notificador);

        return self::SUCCESS;
    }

    // Misma condición que el widget "Fechas vencidas" del panel (ver PanelController::vencidos):
    // pendiente y con inicio ya pasado. Una sola notificación por evento, nunca se repite.
    private function notificarVencidos(Notificador $notificador): void
    {
        $vencidos = Evento::with('responsable')
            ->pendientes()
            ->whereNotNull('responsable_id')
            ->whereNull('notificado_vencido_en')
            ->where('inicio', '<', now())
            ->get();

        foreach ($vencidos as $evento) {
            if ($evento->responsable) {
                $notificador->enviar(
                    $evento->responsable,
                    'evento_vencido',
                    'Evento vencido',
                    "\"{$evento->titulo}\" venció el {$evento->inicio->format('d/m/Y H:i')} sin marcarse como realizado.",
                    ['evento_uuid' => $evento->uuid],
                );
            }

            $evento->update(['notificado_vencido_en' => now()]);
        }
    }

    private function esCorreoValido(string $correo): bool
    {
        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $dominio = strtolower(substr(strrchr($correo, '@'), 1));

        return ! in_array($dominio, self::DOMINIOS_RESERVADOS, true);
    }
}
