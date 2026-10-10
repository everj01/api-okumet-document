<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            // Evita que la notificación "evento_vencido" se repita cada vez que corre el job diario.
            $table->timestamp('notificado_vencido_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('notificado_vencido_en');
        });
    }
};
