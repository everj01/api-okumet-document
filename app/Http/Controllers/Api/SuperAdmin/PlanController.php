<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// CRUD de planes para soporte: no hay destroy, "desactivar" un plan es update con activo=false
// (un plan ya vendido no se puede borrar sin romper las suscripciones que lo referencian).
class PlanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(Plan::orderBy('precio')->get());
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = Plan::create($request->validated());

        return (new PlanResource($plan))->response()->setStatusCode(201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): PlanResource
    {
        $plan->update($request->validated());

        return new PlanResource($plan);
    }
}
