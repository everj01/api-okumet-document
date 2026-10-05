<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $titulo ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FBFAF7; font-family: Arial, Helvetica, sans-serif; color:#2A2B38;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBFAF7; padding:32px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF; border-radius:8px; overflow:hidden; border:1px solid #E7E3D8;">
                <tr>
                    <td style="background-color:#2C3352; padding:20px 28px;">
                        <span style="display:inline-block; background-color:{{ $colorEtiqueta ?? '#E6B85E' }}; color:#2C3352; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; padding:4px 10px; border-radius:4px;">
                            {{ $tipoNotificacion }}
                        </span>
                        <h1 style="margin:10px 0 0; color:#FFFFFF; font-size:19px; font-weight:bold;">{{ $titulo }}</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        {{ $slot }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px; background-color:#FBFAF7; border-top:1px solid #E7E3D8;">
                        <p style="margin:0; font-size:12px; color:#6B6D7E;">{{ config('app.name') }} — este es un correo automático, por favor no respondas.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
