<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IntegracionController extends Controller
{
    private const BASE_URL = 'https://dniruc.apisperu.com/api/v1';

    // DNI/RUC no cambian de un día para otro: se cachean 30 días para no gastar la cuota mensual de apisperu.
    private const TTL_CACHE_DIAS = 30;

    // Variante pública para /registro: aún no hay tenant/usuario autenticado, así que no puede pasar por
    // 'integraciones/dniruc'. Solo RUC (autocompletar datos del negocio al crear el tenant).
    public function rucRegistro(string $numero): JsonResponse
    {
        return $this->dniRuc('ruc', $numero);
    }

    public function dniRuc(string $tipo, string $numero): JsonResponse
    {
        $this->validarParametros($tipo, $numero);

        $clave = "dniruc:{$tipo}:{$numero}";

        $crudo = Cache::remember($clave, now()->addDays(self::TTL_CACHE_DIAS), function () use ($tipo, $numero) {
            return $this->consultarApisperu($tipo, $numero);
        });

        // No encontrado y fallo de servicio (token inválido, límite mensual, red) se tratan igual de cara al
        // frontend: "no encontrado". El detalle real queda en el log para soporte.
        if ($crudo === null || $crudo === false) {
            Cache::forget($clave);

            return response()->json(['message' => 'No se encontró un registro con ese documento.'], 404);
        }

        return response()->json(['data' => $this->normalizar($tipo, $crudo)]);
    }

    private function validarParametros(string $tipo, string $numero): void
    {
        $validador = validator(
            ['tipo' => $tipo, 'numero' => $numero],
            [
                'tipo' => ['required', Rule::in(['dni', 'ruc'])],
                'numero' => ['required', 'digits:'.($tipo === 'ruc' ? 11 : 8)],
            ]
        );

        if ($validador->fails()) {
            throw new ValidationException($validador);
        }
    }

    // null = no encontrado (404 de apisperu); false = fallo del servicio (token, límite mensual, red, etc).
    private function consultarApisperu(string $tipo, string $numero): array|null|false
    {
        $token = config('services.apisperu.token');

        if (! $token) {
            Log::warning('APISPERU_TOKEN no configurado: no se puede consultar DNI/RUC.');

            return false;
        }

        try {
            $respuesta = Http::withToken($token)
                ->timeout(8)
                ->get(self::BASE_URL."/{$tipo}/{$numero}");
        } catch (\Throwable $e) {
            Log::warning("Error de red consultando apisperu ({$tipo}/{$numero}): {$e->getMessage()}");

            return false;
        }

        if ($respuesta->status() === 404) {
            return null;
        }

        if ($respuesta->status() === 429) {
            Log::warning('apisperu devolvió 429: se alcanzó el límite mensual de consultas DNI/RUC.');

            return false;
        }

        if ($respuesta->failed()) {
            Log::warning("apisperu respondió {$respuesta->status()} consultando {$tipo}/{$numero}: {$respuesta->body()}");

            return false;
        }

        $datos = $respuesta->json();

        // apisperu responde 200 con success:false (en vez de 404) cuando el documento no existe.
        if (($datos['success'] ?? true) === false) {
            return null;
        }

        return $datos;
    }

    // apisperu devuelve camelCase (dni) o campos en español sin acentuar (ruc); el contrato hacia
    // el frontend es snake_case en español.
    private function normalizar(string $tipo, array $datos): array
    {
        if ($tipo === 'dni') {
            $apellidoPaterno = trim((string) ($datos['apellidoPaterno'] ?? ''));
            $apellidoMaterno = trim((string) ($datos['apellidoMaterno'] ?? ''));
            $nombres = trim((string) ($datos['nombres'] ?? ''));

            return [
                'numero_documento' => $datos['dni'] ?? null,
                'nombres' => $nombres,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'nombre_completo' => trim("{$apellidoPaterno} {$apellidoMaterno} {$nombres}"),
            ];
        }

        $nombreComercial = trim((string) ($datos['nombreComercial'] ?? ''));

        return [
            'numero_documento' => $datos['ruc'] ?? null,
            'razon_social' => $datos['razonSocial'] ?? null,
            'nombre_comercial' => in_array($nombreComercial, ['', '-'], true) ? null : $nombreComercial,
            'direccion' => $datos['direccion'] ?? null,
            'estado' => $datos['estado'] ?? null,
            'condicion' => $datos['condicion'] ?? null,
            'departamento' => $datos['departamento'] ?? null,
            'provincia' => $datos['provincia'] ?? null,
            'distrito' => $datos['distrito'] ?? null,
        ];
    }
}
