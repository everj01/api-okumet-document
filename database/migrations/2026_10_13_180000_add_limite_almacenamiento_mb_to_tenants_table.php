<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Null = sin límite (tenants existentes quedan sin tope hasta que se les asigne uno).
            $table->unsignedInteger('limite_almacenamiento_mb')->nullable()->after('configuracion');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('limite_almacenamiento_mb');
        });
    }
};
