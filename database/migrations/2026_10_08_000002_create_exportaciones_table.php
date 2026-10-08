<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exportaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido'])->default('pendiente');
            $table->json('filtros');
            $table->unsignedInteger('total_expedientes')->default(0);
            $table->string('archivo_path')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->timestamp('completado_en')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exportaciones');
    }
};
