<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_persona', ['natural', 'juridica'])->default('natural');
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PASAPORTE'])->default('DNI');
            $table->string('numero_documento', 20)->unique();
            $table->string('nombre', 180);
            $table->string('email', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
