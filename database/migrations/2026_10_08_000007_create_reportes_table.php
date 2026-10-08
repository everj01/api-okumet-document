<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Sin tenant_id: es un reporte transversal a todo el sistema, solo lo genera el super admin.
            $table->enum('modulo', ['tenants', 'usuarios', 'clientes', 'expedientes', 'eventos', 'logs']);
            $table->json('filtros');
            $table->json('columnas');
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])->default('pendiente');
            $table->unsignedInteger('total_filas')->default(0);
            $table->string('archivo_path')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->timestamp('completado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
