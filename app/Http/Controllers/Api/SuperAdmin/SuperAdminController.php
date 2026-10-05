<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\EventoResource;
use App\Http\Resources\ExpedienteResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Cliente;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Lecturas transversales para soporte técnico: el TenantScope no aplica porque el usuario es un SuperAdmin, no un User.
class SuperAdminController extends Controller
{
    public function tenants(Request $request): AnonymousResourceCollection
    {
        $tenants = Tenant::withCount('usuarios')
            ->when($request->filled('buscar'), fn (Builder $q) => $q->where(function (Builder $sub) use ($request) {
                $buscar = $request->input('buscar');
                $sub->where('nombre_comercial', 'like', "%{$buscar}%")
                    ->orWhere('razon_social', 'like', "%{$buscar}%")
                    ->orWhere('ruc', 'like', "%{$buscar}%");
            }))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request, ['nombre_comercial', 'created_at'], 'nombre_comercial'))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return TenantResource::collection($tenants);
    }

    public function usuarios(Request $request): AnonymousResourceCollection
    {
        $usuarios = User::with(['rol', 'tenant'])
            ->when($request->filled('buscar'), function (Builder $q) use ($request) {
                $buscar = $request->input('buscar');
                $q->where(fn (Builder $sub) => $sub->where('name', 'like', "%{$buscar}%")->orWhere('email', 'like', "%{$buscar}%"));
            })
            ->when($request->filled('tenant_id'), fn (Builder $q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request, ['name', 'email', 'created_at'], 'name'))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return UsuarioResource::collection($usuarios);
    }

    public function clientes(Request $request): AnonymousResourceCollection
    {
        $clientes = Cliente::with('tenant')
            ->buscar($request->input('buscar'))
            ->when($request->filled('tenant_id'), fn (Builder $q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request, ['nombre', 'created_at'], 'nombre'))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return ClienteResource::collection($clientes);
    }

    public function expedientes(Request $request): AnonymousResourceCollection
    {
        $expedientes = Expediente::with(['tenant', 'cliente', 'abogado'])
            ->buscar($request->input('buscar'))
            ->when($request->filled('tenant_id'), fn (Builder $q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request, ['codigo', 'titulo', 'fecha_inicio', 'created_at'], 'created_at', 'desc'))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return ExpedienteResource::collection($expedientes);
    }

    public function eventos(Request $request): AnonymousResourceCollection
    {
        $eventos = Evento::with(['tenant', 'expediente', 'responsable'])
            ->when($request->filled('buscar'), fn (Builder $q) => $q->where('titulo', 'like', '%'.$request->input('buscar').'%'))
            ->when($request->filled('tenant_id'), fn (Builder $q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->tap(fn (Builder $q) => $this->ordenar($q, $request, ['titulo', 'inicio', 'created_at'], 'inicio', 'desc'))
            ->paginate($request->integer('por_pagina', 15))
            ->withQueryString();

        return EventoResource::collection($eventos);
    }

    /** @param  list<string>  $permitidos */
    private function ordenar(Builder $query, Request $request, array $permitidos, string $porDefecto, string $direccionPorDefecto = 'asc'): void
    {
        $campo = $request->input('orden', $porDefecto);
        $direccion = $request->input('direccion', $direccionPorDefecto);

        $query->orderBy(
            in_array($campo, $permitidos, true) ? $campo : $porDefecto,
            in_array($direccion, ['asc', 'desc'], true) ? $direccion : $direccionPorDefecto
        );
    }
}
