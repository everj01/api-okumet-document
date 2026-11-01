<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suscripciones', function (Blueprint $table) {
            // Cuándo pasó a 'vencida': desde ahí se cuentan los días de gracia antes de bloquear al tenant.
            $table->date('vencida_en')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('suscripciones', function (Blueprint $table) {
            $table->dropColumn('vencida_en');
        });
    }
};
