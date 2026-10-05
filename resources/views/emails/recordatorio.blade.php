<x-mail.layout :tipo-notificacion="$tipoEtiqueta" :titulo="$evento->titulo" :color-etiqueta="$colorEtiqueta">
    <p style="margin:0 0 16px; font-size:14px; line-height:1.5;">
        Te recordamos que tienes {{ $tipoTexto }} próxima:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBFAF7; border-radius:6px; padding:16px;">
        <tr>
            <td style="padding:4px 0; font-size:13px; color:#6B6D7E; width:120px;">Fecha</td>
            <td style="padding:4px 0; font-size:14px; font-weight:bold; color:#2A2B38;">{{ $evento->inicio->format('d/m/Y H:i') }}</td>
        </tr>
        @if ($evento->lugar)
        <tr>
            <td style="padding:4px 0; font-size:13px; color:#6B6D7E;">Lugar</td>
            <td style="padding:4px 0; font-size:14px; color:#2A2B38;">{{ $evento->lugar }}</td>
        </tr>
        @endif
        @if ($evento->expediente)
        <tr>
            <td style="padding:4px 0; font-size:13px; color:#6B6D7E;">Expediente</td>
            <td style="padding:4px 0; font-size:14px; color:#2A2B38;">{{ $evento->expediente->codigo }} — {{ $evento->expediente->titulo }}</td>
        </tr>
        @endif
    </table>

    @if ($evento->notas)
    <p style="margin:16px 0 0; font-size:13px; color:#6B6D7E;">{{ $evento->notas }}</p>
    @endif
</x-mail.layout>
