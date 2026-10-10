<?php

namespace App\Services;

use App\Events\ClaudeUsoActualizado;
use App\Exceptions\IaException;
use App\Models\ClaudeUsageCounter;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// Único punto que sabe cuántas llamadas a Claude lleva un usuario en el mes. AsistenteIa lo consulta
// antes de cada llamada real: cualquier endpoint futuro que use AsistenteIa queda cubierto sin tocar esto.
class LimiteClaudeService
{
    private const LIMITE_ADMIN = 300;

    private const LIMITE_USUARIO = 100;

    // A partir de cuántos créditos restantes se avisa que se están agotando.
    private const UMBRAL_ADVERTENCIA = 5;

    public function __construct(private readonly Notificador $notificador) {}

    public function verificarYRegistrar(User $usuario): void
    {
        try {
            DB::transaction(function () use ($usuario) {
                $contador = $this->contadorBloqueado($usuario);

                if ($contador->llamadas >= $contador->limite) {
                    throw IaException::limiteExcedido($contador->limite);
                }

                $contador->increment('llamadas');
            });
        } catch (IaException $e) {
            // La transacción se revierte sola (no deja el intento a medias); el aviso va fuera, después del rollback.
            $this->emitirYNotificar($usuario, agotado: true);

            throw $e;
        }

        $this->emitirYNotificar($usuario, agotado: false);
    }

    // Se emite el estado en TODA llamada (no solo al tope) para que el pill de créditos del topbar
    // se actualice en vivo sin polling; la notificación solo se dispara al cruzar advertencia/tope,
    // nunca en cada llamada dentro de la zona de advertencia, para no espamear.
    private function emitirYNotificar(User $usuario, bool $agotado): void
    {
        $estado = $this->estado($usuario);

        // Que Reverb esté caído no puede tumbar una llamada a Claude que ya se cobró: si falla, solo
        // se pierde la actualización en vivo del pill (el usuario la ve correcta al refrescar).
        try {
            broadcast(new ClaudeUsoActualizado($usuario, $estado));
        } catch (Throwable $e) {
            Log::warning('claude_uso.broadcast_fallo', ['usuario_id' => $usuario->id, 'mensaje' => $e->getMessage()]);
        }

        if ($agotado && $this->marcarAvisoAgotado($usuario)) {
            $this->notificador->enviar(
                $usuario,
                'ia_credito_agotado',
                'Límite de IA alcanzado',
                "Alcanzaste el límite de {$estado['limite']} consultas a Claude de este mes. Se reinicia el próximo mes.",
            );
        } elseif (! $agotado && $estado['restante'] === self::UMBRAL_ADVERTENCIA) {
            $this->notificador->enviar(
                $usuario,
                'ia_credito_advertencia',
                'Pocos créditos de IA',
                "Te quedan {$estado['restante']} consultas a Claude este mes.",
            );
        }
    }

    /**
     * @return array{usado: int, limite: int, restante: int, advertencia: bool, periodo: string}
     */
    public function estado(User $usuario): array
    {
        $periodo = now()->format('Y-m');

        $contador = ClaudeUsageCounter::query()
            ->where('usuario_id', $usuario->id)
            ->where('periodo', $periodo)
            ->first();

        $usado = $contador?->llamadas ?? 0;
        $limite = $contador?->limite ?? $this->limiteSegunRol($usuario);
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
    private function marcarAvisoAgotado(User $usuario): bool
    {
        $periodo = now()->format('Y-m');

        return ClaudeUsageCounter::query()
            ->where('usuario_id', $usuario->id)
            ->where('periodo', $periodo)
            ->where('aviso_agotado_enviado', false)
            ->update(['aviso_agotado_enviado' => true]) > 0;
    }

    // Bloquea la fila del periodo (creándola si no existe) para que dos llamadas simultáneas no pasen
    // ambas el límite: la segunda espera el lock y ve el contador ya incrementado por la primera.
    private function contadorBloqueado(User $usuario): ClaudeUsageCounter
    {
        $periodo = now()->format('Y-m');

        ClaudeUsageCounter::query()->firstOrCreate(
            ['usuario_id' => $usuario->id, 'periodo' => $periodo],
            ['llamadas' => 0, 'limite' => $this->limiteSegunRol($usuario)],
        );

        return ClaudeUsageCounter::query()
            ->where('usuario_id', $usuario->id)
            ->where('periodo', $periodo)
            ->lockForUpdate()
            ->first();
    }

    private function limiteSegunRol(User $usuario): int
    {
        return $usuario->limite_claude_override
            ?? ($usuario->tieneRol(Rol::ADMIN) ? self::LIMITE_ADMIN : self::LIMITE_USUARIO);
    }
}
