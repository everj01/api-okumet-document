<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claude_usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            // 'YYYY-MM'. No es date: nunca se compara ni se suma, solo se agrupa por valor exacto.
            $table->char('periodo', 7);
            $table->unsignedInteger('llamadas')->default(0);
            // Snapshot del límite vigente al crear la fila del periodo: si el rol cambia a mitad de mes, no afecta retroactivamente.
            $table->unsignedInteger('limite');
            $table->timestamps();

            $table->unique(['usuario_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claude_usage_counters');
    }
};
