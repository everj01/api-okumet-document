<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificacionResource;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notificaciones = Notificacion::where('usuario_id', $request->user()->id)
            ->latest()
            ->paginate(min($request->integer('por_pagina', 15), 30))
            ->withQueryString();

        return NotificacionResource::collection($notificaciones);
    }

    public function resumen(Request $request): JsonResponse
    {
        $noLeidas = Notificacion::where('usuario_id', $request->user()->id)->noLeidas()->count();

        return response()->json(['no_leidas' => $noLeidas]);
    }

    public function leer(Request $request, Notificacion $notificacion): JsonResponse
    {
        abort_unless($notificacion->usuario_id === $request->user()->id, 403);

        if ($notificacion->leida_en === null) {
            $notificacion->update(['leida_en' => now()]);
        }

        return (new NotificacionResource($notificacion))->response();
    }

    public function leerTodas(Request $request): JsonResponse
    {
        Notificacion::where('usuario_id', $request->user()->id)->noLeidas()->update(['leida_en' => now()]);

        return response()->json(['message' => 'Notificaciones marcadas como leídas.']);
    }
}
