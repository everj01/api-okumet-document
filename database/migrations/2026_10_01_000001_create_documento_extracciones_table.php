<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_extracciones', function (Blueprint $table) {
            $table->id();
            // Una extracción vigente por documento: analizar de nuevo cuesta dinero.
            $table->foreignId('documento_id')->unique()->constrained('documentos')->cascadeOnDelete();
            $table->json('campos');
            $table->json('partes');
            $table->json('fechas_clave');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_extracciones');
    }
};
