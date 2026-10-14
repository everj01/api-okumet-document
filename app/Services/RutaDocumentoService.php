<?php

namespace App\Services;

use Carbon\Carbon;

// Único lugar que decide en qué carpeta vive un documento dentro del disco 'documentos'.
// Documentos ya existentes (antes de este servicio) no se tocan: solo aplica a subidas nuevas.
class RutaDocumentoService
{
    public function carpetaHuerfano(?Carbon $fecha = null): string
    {
        $fecha ??= now();

        return "huerfanos/{$fecha->format('Y')}/{$fecha->format('m')}";
    }

    public function carpetaExpediente(int $expedienteId, ?Carbon $fecha = null): string
    {
        $fecha ??= now();

        return "expedientes/{$fecha->format('Y')}/{$fecha->format('m')}/{$expedienteId}";
    }
}
