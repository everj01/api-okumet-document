<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etiquetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('nombre', 60);
            $table->string('color', 7);
            $table->timestamps();

            $table->unique(['tenant_id', 'nombre']);
        });

        Schema::create('documento_etiqueta', function (Blueprint $table) {
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->foreignId('etiqueta_id')->constrained('etiquetas')->cascadeOnDelete();

            $table->primary(['documento_id', 'etiqueta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_etiqueta');
        Schema::dropIfExists('etiquetas');
    }
};
