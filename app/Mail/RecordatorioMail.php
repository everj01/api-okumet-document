<?php

namespace App\Mail;

use App\Models\Evento;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioMail extends Mailable
{
    use Queueable, SerializesModels;

    // Etiqueta + color por tipo de evento, para que el correo sea reconocible de un vistazo.
    private const ETIQUETAS = [
        'audiencia' => ['Audiencia', '#D1443A'],
        'vencimiento' => ['Vencimiento', '#D9A23F'],
        'reunion' => ['Reunión', '#E6B85E'],
        'otro' => ['Recordatorio', '#E6B85E'],
    ];

    public function __construct(public Evento $evento) {}

    public function build(): self
    {
        [$etiqueta, $color] = self::ETIQUETAS[$this->evento->tipo] ?? self::ETIQUETAS['otro'];

        return $this->subject("Recordatorio: {$this->evento->titulo}")
            ->view('emails.recordatorio')
            ->with([
                'evento' => $this->evento,
                'tipoEtiqueta' => $etiqueta,
                'tipoTexto' => mb_strtolower($etiqueta),
                'colorEtiqueta' => $color,
            ]);
    }
}
