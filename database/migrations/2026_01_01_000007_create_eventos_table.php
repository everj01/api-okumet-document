<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['audiencia', 'vencimiento', 'reunion', 'otro'])->default('audiencia');
            $table->string('titulo', 180);
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->string('lugar', 180)->nullable();
            $table->text('notas')->nullable();
            $table->unsignedTinyInteger('recordatorio_dias')->default(1);
            $table->timestamp('recordatorio_enviado_en')->nullable();
            $table->enum('estado', ['pendiente', 'realizado', 'cancelado'])->default('pendiente');
            $table->timestamps();

            $table->index(['inicio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
