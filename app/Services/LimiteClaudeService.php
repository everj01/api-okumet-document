<?php

namespace App\Services;

use App\Events\ClaudeUsoActualizado;
use App\Exceptions\IaException;
use App\Models\ClaudeUsageCounter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// Único punto que sabe cuántas llamadas a Claude lleva un TENANT en el mes: el cupo es un pool
// compartido entre todos sus usuarios (no se distingue por rol). AsistenteIa lo consulta antes de
// cada llamada real: cualquier endpoint futuro que use AsistenteIa queda cubierto sin tocar esto.
class LimiteClaudeService
{
    // Respaldo si el tenant no tiene ninguna suscripción con plan todavía (no debería pasar en producción).
    private const LIMITE_SIN_PLAN = 0;

    // A partir de cuántos créditos restantes se avisa que se están agotando.
    private const UMBRAL_ADVERTENCIA = 5;

    public function __construct(private readonly Notificador $notificador) {}

    public function verificarYRegistrar(User $usuario): void
    {
        $tenant = $usuario->tenant;

        try {
            DB::transaction(function () use ($usuario, $tenant) {
                $contador = $this->contadorBloqueado($usuario, $tenant);

                if ($contador->llamadas >= $contador->limite) {
                    throw IaException::limiteExcedido($contador->limite);
                }

                $contador->increment('llamadas');
            });
        } catch (IaException $e) {
            // La transacción se revierte sola (no deja el intento a medias); el aviso va fuera, después del rollback.
            $this->emitirYNotificar($usuario, $tenant, agotado: true);

            throw $e;
        }

        $this->emitirYNotificar($usuario, $tenant, agotado: false);
    }

    // Se emite el estado en TODA llamada (no solo al tope) para que el pill de créditos del topbar
    // se actualice en vivo sin polling; la notificación solo se dispara al cruzar advertencia/tope,
    // nunca en cada llamada dentro de la zona de advertencia, para no espamear.
    private function emitirYNotificar(User $usuario, Tenant $tenant, bool $agotado): void
    {
        $estado = $this->estadoTenant($tenant);

        // Que Reverb esté caído no puede tumbar una llamada a Claude que ya se cobró: si falla, solo
        // se pierde la actualización en vivo del pill (el usuario la ve correcta al refrescar).
        try {
            broadcast(new ClaudeUsoActualizado($usuario, $estado));
        } catch (Throwable $e) {
            Log::warning('claude_uso.broadcast_fallo', ['usuario_id' => $usuario->id, 'mensaje' => $e->getMessage()]);
        }

        if ($agotado && $this->marcarAvisoAgotado($tenant)) {
            $this->notificador->enviar(
                $usuario,
                'ia_credito_agotado',
                'Límite de IA alcanzado',
                "Tu organización alcanzó el límite de {$estado['limite']} consultas a Claude de este mes. Se reinicia el próximo mes.",
            );
        } elseif (! $agotado && $estado['restante'] === self::UMBRAL_ADVERTENCIA) {
            $this->notificador->enviar(
                $usuario,
                'ia_credito_advertencia',
                'Pocos créditos de IA',
                "Quedan {$estado['restante']} consultas a Claude este mes para tu organización.",
            );
        }
    }

    /**
     * @return array{usado: int, limite: int, restante: int, advertencia: bool, periodo: string}
     */
    public function estado(User $usuario): array
    {
        return $this->estadoTenant($usuario->tenant);
    }

    /**
     * @return array{usado: int, limite: int, restante: int, advertencia: bool, periodo: string}
     */
    public function estadoTenant(Tenant $tenant): array
    {
        $periodo = now()->format('Y-m');

        $contador = ClaudeUsageCounter::query()
            ->where('tenant_id', $tenant->id)
            ->where('periodo', $periodo)
            ->first();

        $usado = $contador?->llamadas ?? 0;
        $limite = $contador?->limite ?? $this->limiteDelPlan($tenant);
        $restante = max(0, $limite - $usado);

        return [
            'usado' => $usado,
            'limite' => $limite,
            'restante' => $restante,
            // El frontend la usa para avisar "quedan pocos créditos" sin tener que repetir el umbral.
            'advertencia' => $restante <= self::UMBRAL_ADVERTENCIA,
            'periodo' => $periodo,
        ];
    }

    // Update condicional atómico: solo el primer intento bloqueado del periodo "gana" el derecho a notificar
    // (evita que cada intento posterior repita el aviso, incluso si dos llegan a la vez).
    private function marcarAvisoAgotado(Tenant $tenant): bool
    {
        $periodo = now()->format('Y-m');

        return ClaudeUsageCounter::query()
            ->where('tenant_id', $tenant->id)
            ->where('periodo', $periodo)
            ->where('aviso_agotado_enviado', false)
            ->update(['aviso_agotado_enviado' => true]) > 0;
    }

    // Bloquea la fila del periodo (creándola si no existe) para que dos llamadas simultáneas de
    // distintos usuarios del mismo tenant no pasen ambas el límite del pool compartido.
    private function contadorBloqueado(User $usuario, Tenant $tenant): ClaudeUsageCounter
    {
        $periodo = now()->format('Y-m');

        ClaudeUsageCounter::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'periodo' => $periodo],
            ['llamadas' => 0, 'limite' => $this->limiteEfectivo($usuario, $tenant)],
        );

        return ClaudeUsageCounter::query()
            ->where('tenant_id', $tenant->id)
            ->where('periodo', $periodo)
            ->lockForUpdate()
            ->first();
    }

    private function limiteDelPlan(Tenant $tenant): int
    {
        return $tenant->suscripcionActual()?->plan?->limite_llamadas_claude ?? self::LIMITE_SIN_PLAN;
    }

    // limite_claude_override es una excepción manual que el SuperAdmin puede setear en un usuario puntual
    // para subir el cupo del tenant POR ENCIMA del que da el plan; ya no es la fuente de verdad por defecto.
    private function limiteEfectivo(User $usuario, Tenant $tenant): int
    {
        return max($this->limiteDelPlan($tenant), $usuario->limite_claude_override ?? 0);
    }
}
