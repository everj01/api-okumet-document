<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UsuarioController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $usuarios = User::with('rol')
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $buscar = $request->input('buscar');
                $query->where(fn ($sub) => $sub->where('name', 'like', "%{$buscar}%")->orWhere('email', 'like', "%{$buscar}%"));
            })
            ->when($request->filled('rol_id'), fn ($query) => $query->where('rol_id', $request->integer('rol_id')))
            ->orderBy('name')
            ->paginate($request->integer('por_pagina', 10))
            ->withQueryString();

        return UsuarioResource::collection($usuarios);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $usuario = User::create($request->validated());

        return (new UsuarioResource($usuario->load('rol')))->response()->setStatusCode(201);
    }

    public function show(User $usuario): UsuarioResource
    {
        return new UsuarioResource($usuario->load('rol'));
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): UsuarioResource
    {
        $datos = $request->validated();

        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        if (! $usuario->activo) {
            $usuario->tokens()->delete();
        }

        return new UsuarioResource($usuario->load('rol'));
    }

    public function destroy(Request $request, User $usuario): JsonResponse
    {
        if ($usuario->is($request->user())) {
            return response()->json(['message' => 'No puedes eliminar tu propio usuario.'], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }
}
