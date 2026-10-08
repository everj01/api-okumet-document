<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reporte\CrearReporteRequest;
use App\Http\Resources\ReporteResource;
use App\Jobs\GenerarReporte;
use App\Models\Reporte;
use App\Services\Reportes\ColumnasReporte;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditor) {}

    public function columnas(string $modulo): JsonResponse
    {
        abort_unless(in_array($modulo, ColumnasReporte::MODULOS, true), 404, 'Módulo no reconocido.');

        return response()->json(ColumnasReporte::disponibles($modulo));
    }

    public function store(CrearReporteRequest $request): JsonResponse
    {
        $reporte = Reporte::create([
            'modulo' => $request->input('modulo'),
            'estado' => 'pendiente',
            'total_filas' => 0,
            'filtros' => [
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin'),
            ],
            'columnas' => $request->input('columnas'),
        ]);

        GenerarReporte::dispatch($reporte);

        $this->auditor->registrar(
            'reporte',
            'reportes',
            "Generó un reporte de \"{$reporte->modulo}\".",
        );

        return (new ReporteResource($reporte))->response()->setStatusCode(201);
    }

    public function show(Reporte $reporte): JsonResponse
    {
        return (new ReporteResource($reporte))->response();
    }

    public function descargar(Reporte $reporte): StreamedResponse
    {
        abort_unless($reporte->completado(), 409, 'El reporte todavía no está listo.');
        abort_if($reporte->archivo_path === null, 404, 'El archivo del reporte ya no está disponible.');

        return Storage::disk('reportes')->download(
            $reporte->archivo_path,
            "reporte-{$reporte->modulo}-{$reporte->uuid}.xlsx",
        );
    }
}
