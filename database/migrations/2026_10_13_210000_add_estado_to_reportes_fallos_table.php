<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_fallos', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('captura_path');
            $table->text('respuesta')->nullable()->after('estado');
            $table->timestamp('resuelto_en')->nullable()->after('respuesta');
        });
    }

    public function down(): void
    {
        Schema::table('reportes_fallos', function (Blueprint $table) {
            $table->dropColumn(['estado', 'respuesta', 'resuelto_en']);
        });
    }
};
