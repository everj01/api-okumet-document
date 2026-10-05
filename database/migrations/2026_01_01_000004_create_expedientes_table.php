<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('titulo', 180);
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('abogado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('materia', 100)->nullable();
            $table->string('juzgado', 150)->nullable();
            $table->enum('estado', ['abierto', 'en_tramite', 'archivado', 'cerrado'])->default('abierto');
            $table->date('fecha_inicio');
            $table->date('fecha_cierre')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
