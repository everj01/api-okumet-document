<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\DocumentoController;
use App\Http\Controllers\Api\DocumentoIaController;
use App\Http\Controllers\Api\EtiquetaController;
use App\Http\Controllers\Api\EventoController;
use App\Http\Controllers\Api\IntegracionController;
use App\Http\Controllers\Api\ExpedienteController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\PanelController;
use App\Http\Controllers\Api\SuperAdmin\SuperAdminAuthController;
use App\Http\Controllers\Api\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\VerificacionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('registro', [AuthController::class, 'registro'])->middleware('throttle:6,1');

// /sistema_admin: ruta aparte para soporte técnico, nunca comparte guard con el login normal.
Route::prefix('sistema-admin')->group(function () {
    Route::post('clave', [SuperAdminAuthController::class, 'clave'])->middleware('throttle:6,1');
    Route::post('login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware(['auth:sanctum', 'super_admin'])->group(function () {
        Route::post('logout', [SuperAdminAuthController::class, 'logout']);
        Route::get('tenants', [SuperAdminController::class, 'tenants']);
        Route::get('usuarios', [SuperAdminController::class, 'usuarios']);
        Route::get('clientes', [SuperAdminController::class, 'clientes']);
        Route::get('expedientes', [SuperAdminController::class, 'expedientes']);
        Route::get('eventos', [SuperAdminController::class, 'eventos']);
    });
});

Route::middleware(['auth:sanctum', 'tenant_user'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('yo', [AuthController::class, 'yo']);
    Route::put('perfil/password', [AuthController::class, 'cambiarPassword']);

    Route::post('verificacion/reenviar', [VerificacionController::class, 'reenviar']);
    Route::post('verificacion/confirmar', [VerificacionController::class, 'confirmar']);

    Route::get('panel', PanelController::class);
    Route::get('catalogos', CatalogoController::class);

    Route::get('tenant', [TenantController::class, 'show']);
    Route::get('configuracion', [ConfiguracionController::class, 'show']);
    Route::middleware('rol:admin')->group(function () {
        Route::put('tenant', [TenantController::class, 'update']);
        Route::post('tenant/logo', [TenantController::class, 'logo']);
        Route::put('configuracion', [ConfiguracionController::class, 'update']);
    });

    Route::apiResource('clientes', ClienteController::class)->except('destroy');
    Route::apiResource('expedientes', ExpedienteController::class)->except('destroy');
    Route::post('expedientes/{expediente}/movimientos', [MovimientoController::class, 'store']);
    Route::post('expedientes/{expediente}/documentos', [DocumentoController::class, 'store']);

    Route::post('documentos/extraer-previa', [DocumentoIaController::class, 'extraerPrevia']);
    Route::get('documentos', [DocumentoController::class, 'index']);
    Route::get('documentos/{documento}', [DocumentoController::class, 'show']);
    Route::get('documentos/{documento}/archivo', [DocumentoController::class, 'archivo']);
    Route::post('documentos/{documento}/resumen', [DocumentoIaController::class, 'resumen']);
    Route::post('documentos/{documento}/preguntas', [DocumentoIaController::class, 'preguntar']);
    Route::post('documentos/{documento}/extraccion', [DocumentoIaController::class, 'extraccion']);
    Route::put('documentos/{documento}/etiquetas', [DocumentoController::class, 'sincronizarEtiquetas']);

    Route::get('etiquetas', [EtiquetaController::class, 'index']);
    Route::post('etiquetas', [EtiquetaController::class, 'store']);
    Route::put('etiquetas/{etiqueta}', [EtiquetaController::class, 'update']);
    Route::delete('etiquetas/{etiqueta}', [EtiquetaController::class, 'destroy']);

    Route::get('integraciones/dniruc/{tipo}/{numero}', [IntegracionController::class, 'dniRuc']);

    Route::apiResource('eventos', EventoController::class)->except(['show', 'destroy']);

    Route::middleware('rol:admin,abogado')->group(function () {
        Route::delete('clientes/{cliente}', [ClienteController::class, 'destroy']);
        Route::delete('expedientes/{expediente}', [ExpedienteController::class, 'destroy']);
        Route::delete('movimientos/{movimiento}', [MovimientoController::class, 'destroy']);
        Route::delete('documentos/{documento}', [DocumentoController::class, 'destroy']);
        Route::delete('eventos/{evento}', [EventoController::class, 'destroy']);
    });

    Route::middleware('rol:admin')->apiResource('usuarios', UsuarioController::class);

    // Disparar manualmente el envío de recordatorios de agenda
    Route::middleware('rol:admin')->post('recordatorios/enviar', function () {
        Artisan::call('okd:recordatorios');

        return response()->json(['mensaje' => trim(Artisan::output())]);
    });
});
