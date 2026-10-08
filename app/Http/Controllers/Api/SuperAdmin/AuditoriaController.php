<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditoriaResource;
use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditoriaController extends Controller
{
    public function logs(Request $request): AnonymousResourceCollection
    {
        $logs = Auditoria::with('tenant')
            ->when($request->filled('buscar'), function (Builder $q) use ($request) {
                $buscar = $request->input('buscar');
                $q->where(fn (Builder $sub) => $sub
                    ->where('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('actor_nombre', 'like', "%{$buscar}%")
                    ->orWhere('actor_email', 'like', "%{$buscar}%"));
            })
            ->when($request->filled('accion'), fn (Builder $q) => $q->where('accion', $request->input('accion')))
            ->when($request->filled('modulo'), fn (Builder $q) => $q->where('modulo', $request->input('modulo')))
            ->when($request->filled('fecha_inicio'), fn (Builder $q) => $q->whereDate('creado_en', '>=', $request->input('fecha_inicio')))
            ->when($request->filled('fecha_fin'), fn (Builder $q) => $q->whereDate('creado_en', '<=', $request->input('fecha_fin')))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return AuditoriaResource::collection($logs);
    }

    private function ordenar(Builder $query, Request $request): void
    {
        $permitidos = ['creado_en', 'actor_nombre', 'accion', 'modulo'];
        $campo = $request->input('orden', 'creado_en');
        $direccion = $request->input('direccion', 'desc');

        $query->orderBy(
            in_array($campo, $permitidos, true) ? $campo : 'creado_en',
            in_array($direccion, ['asc', 'desc'], true) ? $direccion : 'desc'
        );
    }
}
