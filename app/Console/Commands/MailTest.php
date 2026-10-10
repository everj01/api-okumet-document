<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {email}';

    protected $description = 'Envía un correo de prueba para verificar la configuración SMTP';

    public function handle(): int
    {
        $email = $this->argument('email');

        Mail::raw('Este es un correo de prueba desde Okumet Document. Si lo recibes, la configuración SMTP funciona correctamente.', function ($mail) use ($email) {
            $mail->to($email)->subject('Correo de prueba - Okumet Document');
        });

        $this->info("Correo de prueba enviado a {$email}");

        return self::SUCCESS;
    }
}
