<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteFallo\ResolverReporteFalloRequest;
use App\Http\Resources\ReporteFalloResource;
use App\Models\ReporteFallo;
use App\Services\Notificador;
use App\Services\RegistradorAuditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteFalloController extends Controller
{
    public function __construct(
        private readonly Notificador $notificador,
        private readonly RegistradorAuditoria $auditor,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $reportes = ReporteFallo::with(['tenant', 'usuario'])
            ->when($request->filled('buscar'), function (Builder $q) use ($request) {
                $buscar = $request->input('buscar');
                $q->where('comentario', 'like', "%{$buscar}%");
            })
            ->when($request->filled('tenant_id'), fn (Builder $q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('motivo'), fn (Builder $q) => $q->where('motivo', $request->input('motivo')))
            ->when($request->filled('estado'), fn (Builder $q) => $q->where('estado', $request->input('estado')))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return ReporteFalloResource::collection($reportes);
    }

    public function captura(ReporteFallo $reporteFallo): BinaryFileResponse
    {
        abort_if($reporteFallo->captura_path === null, 404, 'Este reporte no tiene una captura adjunta.');

        $ruta = Storage::disk('reportes_fallos')->path($reporteFallo->captura_path);

        abort_unless(is_file($ruta), 404, 'La captura ya no está disponible.');

        return response()->file($ruta);
    }

    public function resolver(ResolverReporteFalloRequest $request, ReporteFallo $reporteFallo): JsonResponse
    {
        $respuesta = $request->validated('respuesta');
        $admin = $request->user();

        // Update + auditoría atómicos: si el log de auditoría falla (ej. el enum de "accion" no tiene
        // el valor usado), el reporte no queda a medias marcado como resuelto sin que quede registro.
        DB::transaction(function () use ($reporteFallo, $respuesta, $admin) {
            $reporteFallo->update([
                'estado' => 'resuelto',
                'respuesta' => $respuesta,
                'resuelto_en' => now(),
            ]);

            $this->auditor->registrar(
                'resuelto',
                'reportes_fallos',
                "Respondió el reporte de falla \"{$reporteFallo->uuid}\".",
                actorNombre: $admin->name,
                actorEmail: $admin->email,
            );
        });

        $this->notificador->enviar(
            $reporteFallo->usuario,
            'reporte_fallo_respondido',
            'Soporte respondió tu reporte',
            $respuesta,
            ['reporte_fallo_uuid' => $reporteFallo->uuid],
        );

        return (new ReporteFalloResource($reporteFallo->load(['tenant', 'usuario'])))->response();
    }

    private function ordenar(Builder $query, Request $request): void
    {
        $permitidos = ['created_at', 'motivo'];
        $campo = $request->input('orden', 'created_at');
        $direccion = $request->input('direccion', 'desc');

        $query->orderBy(
            in_array($campo, $permitidos, true) ? $campo : 'created_at',
            in_array($direccion, ['asc', 'desc'], true) ? $direccion : 'desc'
        );
    }
}
