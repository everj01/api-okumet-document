<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exportacion\CrearExportacionRequest;
use App\Http\Resources\ExportacionResource;
use App\Jobs\GenerarExportacion;
use App\Models\Exportacion;
use App\Services\RegistradorAuditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportacionController extends Controller
{
    public function __construct(private readonly RegistradorAuditoria $auditor) {}

    public function store(CrearExportacionRequest $request): JsonResponse
    {
        $exportacion = Exportacion::create([
            'solicitado_por' => $request->user()->id,
            'estado' => 'pendiente',
            'total_expedientes' => 0,
            'filtros' => [
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_fin' => $request->input('fecha_fin'),
                'expediente_ids' => $request->input('expediente_ids'),
            ],
        ]);

        GenerarExportacion::dispatch($exportacion);

        $this->auditor->registrar(
            'exportacion',
            'exportaciones',
            'Solicitó una exportación de datos del negocio.',
        );

        return (new ExportacionResource($exportacion))->response()->setStatusCode(201);
    }

    public function show(Exportacion $exportacion): JsonResponse
    {
        return (new ExportacionResource($exportacion))->response();
    }

    public function descargar(Exportacion $exportacion): StreamedResponse
    {
        abort_unless($exportacion->completada(), 409, 'La exportación todavía no está lista.');
        abort_if($exportacion->archivo_path === null, 404, 'El archivo de exportación ya no está disponible.');

        return Storage::disk('exportaciones')->download(
            $exportacion->archivo_path,
            "exportacion-{$exportacion->uuid}.zip",
        );
    }
}
