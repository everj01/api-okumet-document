<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

// Único punto que sabe cuánto almacenamiento lleva un tenant: SUM en caliente sobre documentos.tamano,
// sin contador aparte que mantener sincronizado. Documento::destroy hace hard delete, así que el SUM
// siempre refleja el estado real sin necesidad de restar nada al borrar.
class AlmacenamientoService
{
    private const BYTES_POR_MB = 1024 * 1024;

    /**
     * @throws ValidationException si el tenant tiene límite y los bytes entrantes lo superarían.
     */
    public function verificarEspacioPara(Tenant $tenant, int $bytesEntrantes, string $campo): void
    {
        $limiteMb = $this->limiteMb($tenant);

        if ($limiteMb === null) {
            return;
        }

        $limiteBytes = $limiteMb * self::BYTES_POR_MB;
        $usadoBytes = $this->usadoBytes($tenant);

        if ($usadoBytes + $bytesEntrantes > $limiteBytes) {
            $restanteMb = max(0, floor(($limiteBytes - $usadoBytes) / self::BYTES_POR_MB));

            throw ValidationException::withMessages([
                $campo => ["No hay espacio de almacenamiento suficiente: quedan {$restanteMb} MB de {$limiteMb} MB disponibles."],
            ]);
        }
    }

    /**
     * @return array{usado_mb: float, limite_mb: int|null, restante_mb: float|null}
     */
    public function estado(Tenant $tenant): array
    {
        $usadoMb = round($this->usadoBytes($tenant) / self::BYTES_POR_MB, 2);
        $limiteMb = $this->limiteMb($tenant);

        return [
            'usado_mb' => $usadoMb,
            'limite_mb' => $limiteMb,
            'restante_mb' => $limiteMb === null ? null : max(0, round($limiteMb - $usadoMb, 2)),
        ];
    }

    // tenant.limite_almacenamiento_mb es una excepción manual (ej. soporte le abre espacio extra a un
    // caso puntual); si no está seteada, el límite por defecto viene del plan de la suscripción activa.
    private function limiteMb(Tenant $tenant): ?int
    {
        return $tenant->limite_almacenamiento_mb ?? $tenant->suscripcionActual()?->plan?->limite_almacenamiento_mb;
    }

    private function usadoBytes(Tenant $tenant): int
    {
        return (int) Documento::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->sum('tamano');
    }
}
