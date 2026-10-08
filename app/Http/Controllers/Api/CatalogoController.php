<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Etiqueta;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'roles' => Rol::orderBy('id')->get(['id', 'nombre', 'descripcion']),
            'responsables' => User::activos()->orderBy('name')->get(['id', 'name']),
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'numero_documento']),
            'expedientes' => Expediente::abiertos()->orderBy('codigo')->get(['id', 'codigo', 'titulo']),
            'estados_expediente' => Expediente::ESTADOS,
            'tipos_evento' => Evento::TIPOS,
            'etiquetas' => Etiqueta::orderBy('nombre')->get(['id', 'uuid', 'nombre', 'color']),
        ]);
    }
}
