<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expediente\StoreMovimientoRequest;
use App\Http\Resources\MovimientoResource;
use App\Models\Expediente;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;

class MovimientoController extends Controller
{
    public function store(StoreMovimientoRequest $request, Expediente $expediente): JsonResponse
    {
        $movimiento = $expediente->movimientos()->create(
            $request->validated() + ['registrado_por' => $request->user()->id]
        );

        return (new MovimientoResource($movimiento->load('registradoPor')))->response()->setStatusCode(201);
    }

    public function destroy(Movimiento $movimiento): JsonResponse
    {
        $movimiento->delete();

        return response()->json(['message' => 'Movimiento eliminado.']);
    }
}
