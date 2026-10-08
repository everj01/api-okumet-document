<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LimiteClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaudeUsoController extends Controller
{
    public function __construct(private readonly LimiteClaudeService $limite) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->limite->estado($request->user()));
    }
}
