<?php

namespace App\Jobs;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\Reporte;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Reportes\ColumnasReporte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

// Reporte transversal a todo el sistema (sin tenant_id): el super admin elige módulo, rango de
// fechas y columnas, y esto escribe un xlsx de una sola hoja con solo esas columnas.
class GenerarReporte implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(private readonly Reporte $reporte) {}

    public function handle(): void
    {
        $this->reporte->update(['estado' => 'procesando']);

        try {
            $filas = $this->filas();
            $columnas = $this->reporte->columnas;

            $path = tempnam(sys_get_temp_dir(), 'reporte_').'.xlsx';
            $this->escribirExcel($path, $columnas, $filas);

            $relativo = "{$this->reporte->uuid}.xlsx";
            Storage::disk('reportes')->put($relativo, file_get_contents($path));
            unlink($path);

            $this->reporte->update([
                'estado' => 'completado',
                'archivo_path' => $relativo,
                'total_filas' => $filas->count(),
                'completado_en' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('reporte.fallo', ['reporte_id' => $this->reporte->id, 'mensaje' => $e->getMessage()]);

            $this->reporte->update(['estado' => 'fallido', 'error_mensaje' => $e->getMessage()]);
        }
    }

    /** @return Collection<int, array<string, mixed>> una fila por registro, con TODAS las claves disponibles del módulo */
    private function filas(): Collection
    {
        $fechaInicio = $this->reporte->filtros['fecha_inicio'] ?? null;
        $fechaFin = $this->reporte->filtros['fecha_fin'] ?? null;

        return match ($this->reporte->modulo) {
            'tenants' => Tenant::query()
                ->tap(fn (Builder $q) => $this->filtrarFechas($q, 'created_at', $fechaInicio, $fechaFin))
                ->get()
                ->map(fn (Tenant $t) => [
                    'nombre_comercial' => $t->nombre_comercial,
                    'razon_social' => $t->razon_social,
                    'ruc' => $t->ruc,
                    'email' => $t->email,
                    'telefono' => $t->telefono,
                    'activo' => $t->activo ? 'si' : 'no',
                    'created_at' => optional($t->created_at)->toDateTimeString(),
                ]),

            'usuarios' => User::with(['rol', 'tenant'])
                ->tap(fn (Builder $q) => $this->filtrarFechas($q, 'created_at', $fechaInicio, $fechaFin))
                ->get()
                ->map(fn (User $u) => [
                    'name' => $u->name,
                    'email' => $u->email,
                    'rol' => $u->rol?->nombre,
                    'tenant' => $u->tenant?->nombre_comercial,
                    'activo' => $u->activo ? 'si' : 'no',
                    'created_at' => optional($u->created_at)->toDateTimeString(),
                ]),

            'clientes' => Cliente::with('tenant')
                ->tap(fn (Builder $q) => $this->filtrarFechas($q, 'created_at', $fechaInicio, $fechaFin))
                ->get()
                ->map(fn (Cliente $c) => [
                    'nombre' => $c->nombre,
                    'numero_documento' => $c->numero_documento,
                    'tipo_documento' => $c->tipo_documento,
                    'email' => $c->email,
                    'telefono' => $c->telefono,
                    'tenant' => $c->tenant?->nombre_comercial,
                    'created_at' => optional($c->created_at)->toDateTimeString(),
                ]),

            'expedientes' => Expediente::with(['tenant', 'cliente'])
                ->tap(fn (Builder $q) => $this->filtrarFechas($q, 'fecha_inicio', $fechaInicio, $fechaFin))
                ->get()
                ->map(fn (Expediente $e) => [
                    'codigo' => $e->codigo,
                    'titulo' => $e->titulo,
                    'materia' => $e->materia,
                    'juzgado' => $e->juzgado,
                    'estado' => $e->estado,
                    'fecha_inicio' => optional($e->fecha_inicio)->toDateString(),
                    'tenant' => $e->tenant?->nombre_comercial,
                    'cliente' => $e->cliente?->nombre,
                    'created_at' => optional($e->created_at)->toDateTimeString(),
                ]),

            'eventos' => Evento::with('tenant')
                ->tap(fn (Builder $q) => $this->filtrarFechas($q, 'inicio', $fechaInicio, $fechaFin))
                ->get()
                ->map(fn (Evento $ev) => [
                    'titulo' => $ev->titulo,
                    'tipo' => $ev->tipo,
                    'inicio' => optional($ev->inicio)->toDateTimeString(),
                    'fin' => optional($ev->fin)->toDateTimeString(),
                    'estado' => $ev->estado,
                    'tenant' => $ev->tenant?->nombre_comercial,
                    'created_at' => optional($ev->created_at)->toDateTimeString(),
                ]),

            'logs' => Auditoria::with('tenant')
                ->tap(fn (Builder $q) => $this->filtrarFechas($q, 'creado_en', $fechaInicio, $fechaFin))
                ->get()
                ->map(fn (Auditoria $a) => [
                    'accion' => $a->accion,
                    'modulo' => $a->modulo,
                    'actor_nombre' => $a->actor_nombre,
                    'actor_email' => $a->actor_email,
                    'tenant' => $a->tenant?->nombre_comercial,
                    'descripcion' => $a->descripcion,
                    'ip' => $a->ip,
                    'creado_en' => optional($a->creado_en)->toDateTimeString(),
                ]),

            default => collect(),
        };
    }

    private function filtrarFechas(Builder $query, string $columna, ?string $fechaInicio, ?string $fechaFin): void
    {
        $query
            ->when($fechaInicio, fn (Builder $q, string $f) => $q->whereDate($columna, '>=', $f))
            ->when($fechaFin, fn (Builder $q, string $f) => $q->whereDate($columna, '<=', $f));
    }

    /**
     * @param  list<string>  $columnas
     * @param  Collection<int, array<string, mixed>>  $filas
     */
    private function escribirExcel(string $path, array $columnas, Collection $filas): void
    {
        $modulo = $this->reporte->modulo;

        $writer = new Writer;
        $writer->openToFile($path);

        $encabezado = array_map(fn (string $clave) => ColumnasReporte::etiqueta($modulo, $clave), $columnas);
        $writer->addRow(Row::fromValues($encabezado));

        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues(array_map(fn (string $clave) => $fila[$clave] ?? '', $columnas)));
        }

        $writer->close();
    }
}
