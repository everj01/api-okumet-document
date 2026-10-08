<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio')->nullable()->after('fecha_inicio');
        });

        DB::table('expedientes')
            ->whereNull('anio')
            ->update(['anio' => DB::raw('YEAR(fecha_inicio)')]);
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn('anio');
        });
    }
};
