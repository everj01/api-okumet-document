<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Etiqueta\StoreEtiquetaRequest;
use App\Http\Requests\Etiqueta\UpdateEtiquetaRequest;
use App\Http\Resources\EtiquetaResource;
use App\Models\Etiqueta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EtiquetaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EtiquetaResource::collection(Etiqueta::orderBy('nombre')->get());
    }

    public function store(StoreEtiquetaRequest $request): JsonResponse
    {
        $etiqueta = Etiqueta::create($request->validated());

        return (new EtiquetaResource($etiqueta))->response()->setStatusCode(201);
    }

    public function update(UpdateEtiquetaRequest $request, Etiqueta $etiqueta): EtiquetaResource
    {
        $etiqueta->update($request->validated());

        return new EtiquetaResource($etiqueta);
    }

    public function destroy(Etiqueta $etiqueta): JsonResponse
    {
        $etiqueta->delete();

        return response()->json(['message' => 'Etiqueta eliminada.']);
    }
}
