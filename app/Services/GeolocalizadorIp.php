<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ip-api.com: gratis, sin token, 45 req/min. Si falla o la IP es local, no bloquea nada: se guarda el
// log sin país/ciudad.
class GeolocalizadorIp
{
    private const TTL_CACHE_DIAS = 1;

    /** @return array{pais: ?string, ciudad: ?string} */
    public function localizar(?string $ip): array
    {
        $vacio = ['pais' => null, 'ciudad' => null];

        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $vacio;
        }

        return Cache::remember("geoip:{$ip}", now()->addDays(self::TTL_CACHE_DIAS), function () use ($ip, $vacio) {
            try {
                $respuesta = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,country,city']);
            } catch (\Throwable $e) {
                Log::warning("No se pudo geolocalizar la IP {$ip}: {$e->getMessage()}");

                return $vacio;
            }

            if ($respuesta->failed() || $respuesta->json('status') !== 'success') {
                return $vacio;
            }

            return [
                'pais' => $respuesta->json('country'),
                'ciudad' => $respuesta->json('city'),
            ];
        });
    }
}
