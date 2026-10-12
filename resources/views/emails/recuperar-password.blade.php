<x-mail.layout
    tipo-notificacion="Seguridad de la cuenta"
    titulo="Restablece tu contraseña"
    color-etiqueta="#E6B85E"
    :logo-url="$logoUrl ?? null"
    :nombre-negocio="$nombreNegocio ?? null"
    :saludo="'Hola ' . $nombreUsuario . ','"
>
    <p style="margin:0 0 20px; font-size:14px; line-height:1.5;">
        Recibimos una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el botón para elegir una nueva:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:8px 0 24px;">
                <table role="presentation" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="background-color:#2C3352; border-radius:8px;">
                            <a href="{{ $url }}" style="display:inline-block; padding:14px 32px; font-size:14px; font-weight:bold; color:#FFFFFF; text-decoration:none;">
                                Restablecer contraseña
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px; font-size:13px; line-height:1.5; color:#6B6D7E;">
        Este enlace vence en {{ $minutosVigencia }} minutos y solo se puede usar una vez.
    </p>
    <p style="margin:0; font-size:13px; line-height:1.5; color:#6B6D7E;">
        Si tú no solicitaste este cambio, ignora este correo: tu contraseña actual sigue siendo válida.
    </p>
</x-mail.layout>
