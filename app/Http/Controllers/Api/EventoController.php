<?php

namespace App\Http\Controllers\Api;

use App\Events\EventoActualizado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evento\StoreEventoRequest;
use App\Http\Requests\Evento\UpdateEventoRequest;
use App\Http\Resources\EventoResource;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $eventos = Evento::with(['expediente', 'responsable'])
            ->visiblePara($request->user())
            ->entre($request->input('desde'), $request->input('hasta'))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->input('estado')))
            ->when($request->filled('tipo'), fn ($query) => $query->where('tipo', $request->input('tipo')))
            ->when($request->filled('expediente_id'), fn ($query) => $query->where('expediente_id', $request->integer('expediente_id')))
            ->orderBy('inicio')
            ->paginate($request->integer('por_pagina', 50))
            ->withQueryString();

        return EventoResource::collection($eventos);
    }

    public function store(StoreEventoRequest $request): JsonResponse
    {
        $evento = Evento::create($request->validated())->load(['expediente', 'responsable']);

        broadcast(new EventoActualizado($evento, 'creado'))->toOthers();

        return (new EventoResource($evento))->response()->setStatusCode(201);
    }

    public function update(UpdateEventoRequest $request, Evento $evento): EventoResource
    {
        $this->autorizar($request, $evento);

        $evento->update($request->validated());
        $evento->load(['expediente', 'responsable']);

        broadcast(new EventoActualizado($evento, 'actualizado'))->toOthers();

        return new EventoResource($evento);
    }

    public function destroy(Request $request, Evento $evento): JsonResponse
    {
        $this->autorizar($request, $evento);

        broadcast(new EventoActualizado($evento, 'eliminado'))->toOthers();

        $evento->delete();

        return response()->json(['message' => 'Evento eliminado.']);
    }

    // Tocar un evento de un expediente ajeno es tocar ese expediente.
    private function autorizar(Request $request, Evento $evento): void
    {
        if ($evento->expediente !== null) {
            $this->authorize('update', $evento->expediente);
        }
    }
}
