<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes')->cascadeOnDelete();
            $table->enum('estado', ['activa', 'vencida', 'cancelada'])->default('activa');
            $table->date('inicia_en');
            $table->date('termina_en')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
