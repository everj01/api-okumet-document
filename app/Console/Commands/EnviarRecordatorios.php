<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioMail;
use App\Models\Evento;
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

    public function handle(): int
    {
        $eventos = Evento::with(['responsable', 'expediente'])
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

            try {
                Mail::to($correo)->send(new RecordatorioMail($evento));

                $evento->update(['recordatorio_enviado_en' => now()]);
                $enviados++;
            } catch (Throwable $e) {
                Log::warning("No se pudo enviar recordatorio del evento #{$evento->id} a {$correo}: {$e->getMessage()}");
            }
        }

        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
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
