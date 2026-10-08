<x-mail.layout
    tipo-notificacion="Verificación de cuenta"
    titulo="Confirma tu correo"
    color-etiqueta="#8FBF9F"
    :logo-url="$logoUrl ?? null"
    :nombre-negocio="$nombreNegocio ?? null"
    :saludo="'Hola ' . $nombreUsuario . ','"
>
    <p style="margin:0 0 20px; font-size:14px; line-height:1.5;">
        Usa este código para confirmar tu correo y continuar usando tu cuenta:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:8px 0 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" style="background-color:#FBFAF7; border:2px dashed #E6B85E; border-radius:10px;">
                    <tr>
                        <td style="padding:18px 36px; font-size:32px; font-weight:bold; letter-spacing:0.4em; color:#2C3352; font-family: 'Courier New', Courier, monospace;">
                            {{ $codigo }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:13px; line-height:1.5; color:#6B6D7E;">
        Este código vence en {{ $minutosExpiracion ?? 15 }} minutos. Si tú no solicitaste este código, puedes ignorar este correo.
    </p>
</x-mail.layout>
