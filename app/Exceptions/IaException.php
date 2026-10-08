<?php

namespace App\Exceptions;

use RuntimeException;

// Error de IA con un status HTTP ya decidido; el mensaje es de cara al usuario.
class IaException extends RuntimeException
{
    private function __construct(string $mensaje, public readonly int $status, public readonly ?string $codigo = null)
    {
        parent::__construct($mensaje);
    }

    public static function limiteExcedido(int $limite): self
    {
        return new self(
            "Alcanzaste el límite de {$limite} consultas a Claude de este mes. Se reinicia el próximo mes.",
            429,
            'claude_limit_exceeded',
        );
    }

    public static function sinConfigurar(): self
    {
        return new self('La integración con Claude no está configurada (falta ANTHROPIC_API_KEY).', 503);
    }

    public static function sinTexto(): self
    {
        return new self('El documento no tiene texto extraído.', 422);
    }

    public static function demasiadoLargo(int $caracteres, int $limite): self
    {
        return new self(
            "El documento es demasiado extenso para analizarlo en una sola pasada ({$caracteres} caracteres, límite {$limite}). ".
            'No se analizó ninguna parte: hay que dividir el documento antes de volver a intentarlo.',
            422
        );
    }

    public static function servicio(string $detalle): self
    {
        return new self("El servicio de IA no pudo completar la solicitud: {$detalle}", 502);
    }

    public static function respuestaInesperada(): self
    {
        return new self('El servicio de IA devolvió una respuesta que no se pudo interpretar.', 502);
    }
}
