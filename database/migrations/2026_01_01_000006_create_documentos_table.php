<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->string('nombre', 180);
            $table->string('ruta', 255);
            $table->unsignedBigInteger('tamano');
            $table->unsignedInteger('paginas')->default(0);
            $table->longText('texto')->nullable();
            $table->text('resumen')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->fullText(['nombre', 'texto']);
        });

        Schema::create('documento_paginas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->longText('texto')->nullable();

            $table->unique(['documento_id', 'numero']);
        });

        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('pregunta');
            $table->text('respuesta');
            $table->json('paginas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
        Schema::dropIfExists('documento_paginas');
        Schema::dropIfExists('documentos');
    }
};
