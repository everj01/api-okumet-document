<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claude_usage_counters', function (Blueprint $table) {
            // Evitan que la notificación de advertencia/tope se repita en cada llamada bloqueada del periodo.
            $table->boolean('aviso_agotado_enviado')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('claude_usage_counters', function (Blueprint $table) {
            $table->dropColumn('aviso_agotado_enviado');
        });
    }
};
