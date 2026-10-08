<?php

namespace App\Services;

use App\Exceptions\IaException;
use App\Models\ClaudeUsageCounter;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Único punto que sabe cuántas llamadas a Claude lleva un usuario en el mes. AsistenteIa lo consulta
// antes de cada llamada real: cualquier endpoint futuro que use AsistenteIa queda cubierto sin tocar esto.
class LimiteClaudeService
{
    private const LIMITE_ADMIN = 300;

    private const LIMITE_USUARIO = 100;

    // A partir de cuántos créditos restantes se avisa que se están agotando.
    private const UMBRAL_ADVERTENCIA = 5;

    public function verificarYRegistrar(User $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            $contador = $this->contadorBloqueado($usuario);

            if ($contador->llamadas >= $contador->limite) {
                throw IaException::limiteExcedido($contador->limite);
            }

            $contador->increment('llamadas');
        });
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
        return $usuario->tieneRol(Rol::ADMIN) ? self::LIMITE_ADMIN : self::LIMITE_USUARIO;
    }
}
