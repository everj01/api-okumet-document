<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $titulo ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F1EEE4; font-family: Arial, Helvetica, sans-serif; color:#2A2B38;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F1EEE4; padding:40px 0;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; max-width:580px; background-color:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(44,51,82,0.08), 0 8px 24px rgba(44,51,82,0.06);">

                <tr>
                    <td style="padding:28px 32px 20px; border-bottom:1px solid #EFEBDF;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align:middle;">
                                    @if (!empty($logoUrl))
                                        <img src="{{ $logoUrl }}" alt="{{ $nombreNegocio ?? config('app.name') }}" height="32" style="height:32px; max-width:160px; object-fit:contain; display:block;">
                                    @else
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:30px; height:30px; background-color:#E6B85E; border-radius:7px; text-align:center; vertical-align:middle; font-size:14px; font-weight:bold; color:#2C3352; font-family: Arial, Helvetica, sans-serif;">{{ mb_strtoupper(mb_substr($nombreNegocio ?? config('app.name'), 0, 1)) }}</td>
                                                <td style="padding-left:10px; font-size:15px; font-weight:bold; color:#2C3352;">{{ $nombreNegocio ?? config('app.name') }}</td>
                                            </tr>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="background-color:#2C3352; padding:24px 32px;">
                        <span style="display:inline-block; background-color:{{ $colorEtiqueta ?? '#E6B85E' }}; color:#2C3352; font-size:11px; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; padding:5px 11px; border-radius:20px;">
                            {{ $tipoNotificacion }}
                        </span>
                        <h1 style="margin:12px 0 0; color:#FFFFFF; font-size:21px; font-weight:bold; line-height:1.3;">{{ $titulo }}</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px;">
                        @isset($saludo)
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.5;">{{ $saludo }}</p>
                        @endisset
                        {{ $slot }}
                    </td>
                </tr>

                <tr>
                    <td style="padding:20px 32px; background-color:#FBFAF7; border-top:1px solid #EFEBDF;">
                        <p style="margin:0 0 4px; font-size:12px; font-weight:bold; color:#2A2B38;">{{ $nombreNegocio ?? config('app.name') }}</p>
                        @if (!empty($pieExtra))
                            <p style="margin:0 0 8px; font-size:12px; color:#6B6D7E;">{{ $pieExtra }}</p>
                        @endif
                        <p style="margin:0; font-size:11px; color:#9A9CAD;">Este es un correo automático, por favor no respondas directamente.</p>
                    </td>
                </tr>
            </table>

            <p style="margin:16px 0 0; font-size:11px; color:#9A9CAD; width:100%; max-width:580px;">Generado por Okumet Document</p>
        </td>
    </tr>
</table>
</body>
</html>
