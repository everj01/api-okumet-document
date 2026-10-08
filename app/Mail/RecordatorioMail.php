<?php

namespace App\Mail;

use App\Models\Evento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    // Etiqueta + color por tipo de evento, para que el correo sea reconocible de un vistazo.
    private const ETIQUETAS = [
        'audiencia' => ['Audiencia', '#D1443A'],
        'vencimiento' => ['Vencimiento', '#D9A23F'],
        'reunion' => ['Reunión', '#E6B85E'],
        'otro' => ['Recordatorio', '#E6B85E'],
        'plazo_absolver' => ['Plazo para absolver', '#D9A23F'],
        'plazo_apelar' => ['Plazo para apelar', '#D9A23F'],
        'actuacion_prueba' => ['Actuación de prueba', '#D1443A'],
        'sentencia_1_instancia' => ['Sentencia 1.ª instancia', '#D1443A'],
        'sentencia_2_instancia' => ['Sentencia 2.ª instancia', '#D1443A'],
    ];

    public function __construct(public Evento $evento) {}

    public function build(): self
    {
        [$etiqueta, $color] = self::ETIQUETAS[$this->evento->tipo] ?? self::ETIQUETAS['otro'];

        $tenant = $this->evento->tenant;

        return $this->subject("Recordatorio: {$this->evento->titulo}")
            ->view('emails.recordatorio')
            ->with([
                'evento' => $this->evento,
                'tipoEtiqueta' => $etiqueta,
                'tipoTexto' => mb_strtolower($etiqueta),
                'colorEtiqueta' => $color,
                'logoUrl' => $tenant?->logo_path ? Storage::disk('logos')->url($tenant->logo_path) : null,
                'nombreNegocio' => $tenant?->nombre_comercial,
            ]);
    }
}
