<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteFallo\StoreReporteFalloRequest;
use App\Http\Resources\ReporteFalloResource;
use App\Models\ReporteFallo;
use Illuminate\Http\JsonResponse;

class ReporteFalloController extends Controller
{
    public function store(StoreReporteFalloRequest $request): JsonResponse
    {
        $capturaPath = $request->hasFile('captura')
            ? $request->file('captura')->store('', 'reportes_fallos')
            : null;

        $reporte = ReporteFallo::create([
            'usuario_id' => $request->user()->id,
            'motivo' => $request->validated('motivo'),
            'comentario' => $request->validated('comentario'),
            'ruta' => $request->validated('ruta'),
            'captura_path' => $capturaPath,
            'estado' => 'pendiente',
        ]);

        return (new ReporteFalloResource($reporte))->response()->setStatusCode(201);
    }
}
