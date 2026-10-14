<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_fallos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('motivo', 40);
            $table->text('comentario')->nullable();
            $table->string('ruta')->nullable();
            $table->string('captura_path')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index('motivo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_fallos');
    }
};
