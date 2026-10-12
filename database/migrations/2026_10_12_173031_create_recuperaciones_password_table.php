<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recuperaciones_password', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // sha256 del token real: si la BD se filtra, no alcanza para generar el enlace de nadie.
            $table->string('token', 64);
            $table->timestamp('expira_en');
            $table->timestamp('usado_en')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recuperaciones_password');
    }
};
