<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlmacenamientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmacenamientoController extends Controller
{
    public function __construct(private readonly AlmacenamientoService $almacenamiento) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->almacenamiento->estado($request->user()->tenant));
    }
}
