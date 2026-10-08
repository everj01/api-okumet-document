<?php

namespace App\Services;

// Detección ligera por regex, sin dependencia externa: alcanza para lo que necesita un log de accesos.
class AnalizadorUserAgent
{
    public function navegador(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => null,
        };
    }

    public function sistemaOperativo(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };
    }
}
