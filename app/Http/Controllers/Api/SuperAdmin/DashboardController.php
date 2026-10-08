<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ClaudeUsageCounter;
use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Lecturas transversales de todo el sistema para el panel de super admin: el TenantScope no aplica
// porque quien llama es un SuperAdmin, no un User (ver TenantScope y el comentario de SuperAdminController).
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $meses = $this->ultimosMeses();

        return response()->json([
            'crecimiento' => $this->crecimiento($meses),
            'actividad_casos' => $this->actividadCasos($meses),
            'uso_ia' => $this->usoIa($meses),
            'tenants_cerca_limite' => $this->tenantsCercaLimite(),
            'estado_negocios' => $this->estadoNegocios(),
            'expedientes_por_estado' => $this->expedientesPorEstado(),
        ]);
    }

    /** @return list<string> los últimos 12 periodos 'YYYY-MM', de más antiguo a más reciente */
    private function ultimosMeses(): array
    {
        return collect(range(11, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'))
            ->all();
    }

    /**
     * @param  list<string>  $meses
     * @return list<array{mes: string, tenants_nuevos: int, usuarios_nuevos: int}>
     */
    private function crecimiento(array $meses): array
    {
        $tenants = $this->contarPorMes(Tenant::query(), 'created_at');
        $usuarios = $this->contarPorMes(User::query(), 'created_at');

        return collect($meses)->map(fn (string $mes) => [
            'mes' => $mes,
            'tenants_nuevos' => (int) $tenants->get($mes, 0),
            'usuarios_nuevos' => (int) $usuarios->get($mes, 0),
        ])->all();
    }

    /**
     * @param  list<string>  $meses
     * @return list<array{mes: string, expedientes_abiertos: int, expedientes_cerrados: int, documentos_subidos: int}>
     */
    private function actividadCasos(array $meses): array
    {
        $abiertos = $this->contarPorMes(Expediente::query(), 'created_at');
        $cerrados = $this->contarPorMes(Expediente::query()->whereNotNull('fecha_cierre'), 'fecha_cierre');
        $documentos = $this->contarPorMes(Documento::query(), 'created_at');

        return collect($meses)->map(fn (string $mes) => [
            'mes' => $mes,
            'expedientes_abiertos' => (int) $abiertos->get($mes, 0),
            'expedientes_cerrados' => (int) $cerrados->get($mes, 0),
            'documentos_subidos' => (int) $documentos->get($mes, 0),
        ])->all();
    }

    /**
     * @param  list<string>  $meses
     * @return list<array{mes: string, llamadas: int}>
     */
    private function usoIa(array $meses): array
    {
        $llamadas = ClaudeUsageCounter::query()
            ->whereIn('periodo', $meses)
            ->selectRaw('periodo, SUM(llamadas) as total')
            ->groupBy('periodo')
            ->pluck('total', 'periodo');

        return collect($meses)->map(fn (string $mes) => [
            'mes' => $mes,
            'llamadas' => (int) ($llamadas[$mes] ?? 0),
        ])->all();
    }

    /** @return list<array{tenant: string, usado: int, limite: int}> */
    private function tenantsCercaLimite(): array
    {
        // selectRaw/havingRaw/orderByRaw no reciben el prefijo de tabla (DB_PREFIX) como sí lo hacen
        // join/groupBy, que son expresiones estructuradas: hay que prefijar estos a mano.
        $prefijo = DB::connection()->getTablePrefix();

        return ClaudeUsageCounter::query()
            ->join('users', 'users.id', '=', 'claude_usage_counters.usuario_id')
            ->join('tenants', 'tenants.id', '=', 'users.tenant_id')
            ->where('claude_usage_counters.periodo', now()->format('Y-m'))
            ->selectRaw("{$prefijo}tenants.nombre_comercial as tenant, SUM({$prefijo}claude_usage_counters.llamadas) as usado, SUM({$prefijo}claude_usage_counters.limite) as limite")
            ->groupBy('tenants.id', 'tenants.nombre_comercial')
            ->havingRaw("SUM({$prefijo}claude_usage_counters.llamadas) >= 0.8 * SUM({$prefijo}claude_usage_counters.limite)")
            ->orderByRaw("SUM({$prefijo}claude_usage_counters.llamadas) / SUM({$prefijo}claude_usage_counters.limite) DESC")
            ->limit(5)
            ->get()
            ->map(fn ($fila) => [
                'tenant' => $fila->tenant,
                'usado' => (int) $fila->usado,
                'limite' => (int) $fila->limite,
            ])
            ->all();
    }

    /** @return array{tenants_activos: int, tenants_inactivos: int} */
    private function estadoNegocios(): array
    {
        return [
            'tenants_activos' => Tenant::where('activo', true)->count(),
            'tenants_inactivos' => Tenant::where('activo', false)->count(),
        ];
    }

    /** @return list<array{estado: string, total: int}> */
    private function expedientesPorEstado(): array
    {
        return Expediente::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
            ->map(fn ($fila) => ['estado' => $fila->estado, 'total' => (int) $fila->total])
            ->all();
    }

    /** @return Collection<string, int> total por periodo 'YYYY-MM' */
    private function contarPorMes(Builder $query, string $columna): Collection
    {
        return $query
            ->selectRaw("DATE_FORMAT({$columna}, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');
    }
}
