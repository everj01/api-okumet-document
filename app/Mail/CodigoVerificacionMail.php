<?php

namespace App\Mail;

use App\Models\CodigoVerificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CodigoVerificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CodigoVerificacion $codigoVerificacion) {}

    public function build(): self
    {
        $usuario = $this->codigoVerificacion->user;
        $tenant = $usuario->tenant;

        return $this->subject('Confirma tu correo')
            ->view('emails.verificacion')
            ->with([
                'codigo' => $this->codigoVerificacion->codigo,
                'nombreUsuario' => $usuario->name,
                'minutosExpiracion' => (int) now()->diffInMinutes($this->codigoVerificacion->expira_en),
                'logoUrl' => $tenant?->logo_path ? Storage::disk('logos')->url($tenant->logo_path) : null,
                'nombreNegocio' => $tenant?->nombre_comercial,
            ]);
    }
}
