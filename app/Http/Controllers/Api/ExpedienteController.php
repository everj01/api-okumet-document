<?php

namespace App\Http\Controllers\Api;

use App\Events\ExpedienteActualizado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expediente\StoreExpedienteRequest;
use App\Http\Requests\Expediente\UpdateExpedienteRequest;
use App\Http\Resources\ExpedienteResource;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpedienteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $expedientes = Expediente::with(['cliente', 'abogado'])
            ->visiblePara($request->user())
            ->withCount('documentos')
            ->buscar($request->input('buscar'))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->input('estado')))
            ->when($request->filled('cliente_id'), fn ($query) => $query->where('cliente_id', $request->integer('cliente_id')))
            ->when($request->filled('abogado_id'), fn ($query) => $query->where('abogado_id', $request->integer('abogado_id')))
            ->latest('fecha_inicio')
            ->paginate($request->integer('por_pagina', 10))
            ->withQueryString();

        return ExpedienteResource::collection($expedientes);
    }

    public function store(StoreExpedienteRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $datos['anio'] ??= date('Y', strtotime($datos['fecha_inicio']));

        $expediente = Expediente::create($datos)->load(['cliente', 'abogado']);

        broadcast(new ExpedienteActualizado($expediente, 'creado'))->toOthers();

        return (new ExpedienteResource($expediente))->response()->setStatusCode(201);
    }

    public function show(Expediente $expediente): ExpedienteResource
    {
        $this->authorize('view', $expediente);

        $expediente->load([
            'cliente',
            'abogado',
            'movimientos.registradoPor',
            'documentos.subidoPor',
            'eventos.responsable',
        ]);

        return new ExpedienteResource($expediente);
    }

    public function update(UpdateExpedienteRequest $request, Expediente $expediente): ExpedienteResource
    {
        $this->authorize('update', $expediente);

        $expediente->update($request->validated());
        $expediente->load(['cliente', 'abogado']);

        broadcast(new ExpedienteActualizado($expediente, 'actualizado'))->toOthers();

        return new ExpedienteResource($expediente);
    }

    public function destroy(Expediente $expediente): JsonResponse
    {
        $this->authorize('delete', $expediente);

        broadcast(new ExpedienteActualizado($expediente, 'eliminado'))->toOthers();

        $expediente->delete();

        return response()->json(['message' => 'Expediente eliminado.']);
    }
}
