<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class RecuperarPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $usuario,
        public string $url,
        public int $minutosVigencia,
    ) {}

    public function build(): self
    {
        $tenant = $this->usuario->tenant;

        return $this->subject('Restablece tu contraseña')
            ->view('emails.recuperar-password')
            ->with([
                'url' => $this->url,
                'nombreUsuario' => $this->usuario->name,
                'minutosVigencia' => $this->minutosVigencia,
                'logoUrl' => $tenant?->logo_path ? Storage::disk('logos')->url($tenant->logo_path) : null,
                'nombreNegocio' => $tenant?->nombre_comercial,
            ]);
    }
}
