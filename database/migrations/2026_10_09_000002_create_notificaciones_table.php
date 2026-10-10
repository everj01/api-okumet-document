<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->string('titulo', 180);
            $table->text('mensaje');
            $table->json('data')->nullable();
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'leida_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
