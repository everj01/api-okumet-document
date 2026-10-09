<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null = usa el default según rol (300 admin / 100 resto). Con un número, ese usuario
            // puntual queda fijo en ese límite sin tocar ninguna lógica: solo se edita esta columna.
            $table->unsignedInteger('limite_claude_override')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('limite_claude_override');
        });
    }
};
