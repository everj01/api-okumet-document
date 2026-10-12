<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dni', 20)->nullable()->after('telefono');
            $table->string('colegiatura', 20)->nullable()->after('dni');
            $table->boolean('abogado_habilitado')->nullable()->after('colegiatura');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dni', 'colegiatura', 'abogado_habilitado']);
        });
    }
};
