<?php

namespace App\Jobs;

use App\Models\Documento;
use App\Models\Evento;
use App\Models\Exportacion;
use App\Models\Expediente;
use App\Models\Movimiento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;
use ZipArchive;

// Corre en cola porque el volumen de documentos (archivos reales + lectura de BD) no es cosa de un request síncrono.
// Nunca corre bajo un usuario autenticado, así que todas las consultas filtran tenant_id a mano
// (TenantScope no filtra fuera de un request: ver App\Models\Scopes\TenantScope).
class GenerarExportacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(private readonly Exportacion $exportacion) {}

    public function handle(): void
    {
        $this->exportacion->update(['estado' => 'procesando']);

        try {
            [$archivoPath, $total] = $this->generar();

            $this->exportacion->update([
                'estado' => 'completado',
                'archivo_path' => $archivoPath,
                'total_expedientes' => $total,
                'completado_en' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('exportacion.fallo', ['exportacion_id' => $this->exportacion->id, 'mensaje' => $e->getMessage()]);

            $this->exportacion->update(['estado' => 'fallido', 'error_mensaje' => $e->getMessage()]);
        }
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function generar(): array
    {
        $tenantId = $this->exportacion->tenant_id;
        $filtros = $this->exportacion->filtros;

        $expedientes = Expediente::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $filtros['expediente_ids'] ?? [])
            ->when($filtros['fecha_inicio'] ?? null, fn ($q, $fecha) => $q->where('fecha_inicio', '>=', $fecha))
            ->when($filtros['fecha_fin'] ?? null, fn ($q, $fecha) => $q->where('fecha_inicio', '<=', $fecha))
            ->with('cliente')
            ->get();

        $zipPath = tempnam(sys_get_temp_dir(), 'exportacion_').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $filasExpedientes = [];
        $filasClientes = [];
        $filasMovimientos = [];
        $filasEventos = [];
        $filasDocumentos = [];
        $filasExtracciones = [];
        $clientesVistos = [];

        foreach ($expedientes as $expediente) {
            $carpetaCliente = $this->sanitizar($expediente->cliente->nombre ?? 'sin-cliente');
            $carpetaExpediente = $this->sanitizar("{$expediente->codigo}_{$expediente->titulo}");
            $base = "{$carpetaCliente}/{$carpetaExpediente}/";

            $filasExpedientes[] = [
                $expediente->uuid,
                $expediente->codigo,
                $expediente->titulo,
                $expediente->cliente->nombre ?? '',
                $expediente->materia,
                $expediente->juzgado,
                $expediente->estado,
                optional($expediente->fecha_inicio)->toDateString(),
                optional($expediente->fecha_cierre)->toDateString(),
                $expediente->descripcion,
            ];

            if ($expediente->cliente !== null && ! isset($clientesVistos[$expediente->cliente->id])) {
                $clientesVistos[$expediente->cliente->id] = true;
                $cliente = $expediente->cliente;
                $filasClientes[] = [
                    $cliente->uuid,
                    $cliente->tipo_persona,
                    $cliente->tipo_documento,
                    $cliente->numero_documento,
                    $cliente->nombre,
                    $cliente->email,
                    $cliente->telefono,
                    $cliente->direccion,
                    $cliente->activo ? 'si' : 'no',
                ];
            }

            foreach (Movimiento::where('expediente_id', $expediente->id)->get() as $movimiento) {
                $filasMovimientos[] = [
                    $movimiento->uuid,
                    $expediente->uuid,
                    optional($movimiento->fecha)->toDateString(),
                    $movimiento->titulo,
                    $movimiento->detalle,
                ];
            }

            foreach (Evento::where('expediente_id', $expediente->id)->get() as $evento) {
                $filasEventos[] = [
                    $evento->uuid,
                    $expediente->uuid,
                    $evento->tipo,
                    $evento->titulo,
                    optional($evento->inicio)->toDateTimeString(),
                    optional($evento->fin)->toDateTimeString(),
                    $evento->lugar,
                    $evento->estado,
                ];
            }

            foreach (Documento::where('expediente_id', $expediente->id)->with('extraccion')->get() as $documento) {
                $rutaEnZip = $base.$this->sanitizar($documento->nombre);
                $rutaLocal = Storage::disk('documentos')->path($documento->ruta);

                if (is_file($rutaLocal)) {
                    $zip->addFile($rutaLocal, $rutaEnZip);
                }

                $filasDocumentos[] = [
                    $documento->uuid,
                    $expediente->uuid,
                    $documento->nombre,
                    $documento->tamano,
                    $documento->paginas,
                    $documento->resumen,
                    $rutaEnZip,
                ];

                if ($documento->extraccion !== null) {
                    $filasExtracciones[] = [
                        $documento->uuid,
                        json_encode($documento->extraccion->campos, JSON_UNESCAPED_UNICODE),
                        json_encode($documento->extraccion->partes, JSON_UNESCAPED_UNICODE),
                        json_encode($documento->extraccion->fechas_clave, JSON_UNESCAPED_UNICODE),
                    ];
                }
            }
        }

        $xlsxPath = tempnam(sys_get_temp_dir(), 'exportacion_').'.xlsx';
        $this->escribirExcel($xlsxPath, [
            'Expedientes' => [['uuid', 'codigo', 'titulo', 'cliente', 'materia', 'juzgado', 'estado', 'fecha_inicio', 'fecha_cierre', 'descripcion'], ...$filasExpedientes],
            'Clientes' => [['uuid', 'tipo_persona', 'tipo_documento', 'numero_documento', 'nombre', 'email', 'telefono', 'direccion', 'activo'], ...$filasClientes],
            'Movimientos' => [['uuid', 'expediente_uuid', 'fecha', 'titulo', 'detalle'], ...$filasMovimientos],
            'Eventos' => [['uuid', 'expediente_uuid', 'tipo', 'titulo', 'inicio', 'fin', 'lugar', 'estado'], ...$filasEventos],
            'Documentos' => [['uuid', 'expediente_uuid', 'nombre', 'tamano', 'paginas', 'resumen', 'ruta_en_zip'], ...$filasDocumentos],
            'Extracciones' => [['documento_uuid', 'campos', 'partes', 'fechas_clave'], ...$filasExtracciones],
        ]);

        $zip->addFile($xlsxPath, 'datos.xlsx');
        $zip->close();
        unlink($xlsxPath);

        $relativo = "{$tenantId}/{$this->exportacion->uuid}.zip";
        Storage::disk('exportaciones')->put($relativo, file_get_contents($zipPath));
        unlink($zipPath);

        return [$relativo, $expedientes->count()];
    }

    /**
     * @param array<string, array<int, array<int, mixed>>> $hojas nombre de hoja => filas (la primera es el header)
     */
    private function escribirExcel(string $path, array $hojas): void
    {
        $writer = new Writer;
        $writer->openToFile($path);

        $primera = true;

        foreach ($hojas as $nombre => $filas) {
            if (! $primera) {
                $writer->addNewSheetAndMakeItCurrent();
            }

            $writer->getCurrentSheet()->setName($nombre);

            foreach ($filas as $fila) {
                $writer->addRow(Row::fromValues($fila));
            }

            $primera = false;
        }

        $writer->close();
    }

    // Nombre de carpeta/archivo legible dentro del zip, sin separadores de ruta ni caracteres que rompan el zip.
    private function sanitizar(string $valor): string
    {
        $limpio = preg_replace('/[\/\\\\:*?"<>|]+/', '-', trim($valor));
        $limpio = trim($limpio, '. ');

        return $limpio === '' ? 'sin-nombre' : $limpio;
    }
}
