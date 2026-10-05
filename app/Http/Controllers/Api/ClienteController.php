<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $clientes = Cliente::withCount(['expedientes' => fn ($query) => $query->visiblePara($request->user())])
            ->buscar($request->input('buscar'))
            ->when($request->filled('activo'), fn ($query) => $query->where('activo', $request->boolean('activo')))
            ->orderBy('nombre')
            ->paginate($request->integer('por_pagina', 10))
            ->withQueryString();

        return ClienteResource::collection($clientes);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create($request->validated() + ['registrado_por' => $request->user()->id]);

        return (new ClienteResource($cliente))->response()->setStatusCode(201);
    }

    public function show(Request $request, Cliente $cliente): ClienteResource
    {
        // El historial de casos del cliente, limitado a los que el usuario puede ver.
        $visibles = fn ($query) => $query->visiblePara($request->user());

        $cliente->load(['expedientes' => fn ($query) => $visibles($query)->with('abogado')->latest('fecha_inicio')])
            ->loadCount(['expedientes' => $visibles]);

        return new ClienteResource($cliente);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): ClienteResource
    {
        $cliente->update($request->validated());

        return new ClienteResource($cliente);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        if ($cliente->expedientes()->exists()) {
            return response()->json([
                'message' => 'El cliente tiene expedientes registrados. Desactívalo en lugar de eliminarlo.',
            ], 422);
        }

        $cliente->delete();

        return response()->json(['message' => 'Cliente eliminado.']);
    }
}
