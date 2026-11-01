<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            // El cupo de IA pasa a ser un pool único por tenant (no separado por rol admin/usuario).
            $table->unsignedInteger('limite_llamadas_claude')->default(0)->after('precio');
            $table->unsignedInteger('limite_almacenamiento_mb')->nullable()->after('limite_usuarios');
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn(['limite_llamadas_claude_admin', 'limite_llamadas_claude_usuario']);
        });
    }

    public function down(): void
    {
        Schema::table('planes', function (Blueprint $table) {
            $table->unsignedInteger('limite_llamadas_claude_admin')->default(0)->after('precio');
            $table->unsignedInteger('limite_llamadas_claude_usuario')->default(0)->after('limite_llamadas_claude_admin');
        });

        Schema::table('planes', function (Blueprint $table) {
            $table->dropColumn(['limite_llamadas_claude', 'limite_almacenamiento_mb']);
        });
    }
};
