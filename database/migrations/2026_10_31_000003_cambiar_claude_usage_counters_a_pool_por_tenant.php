<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claude_usage_counters', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropUnique(['usuario_id', 'periodo']);
        });

        // El cupo pasa de ser por usuario a ser un pool por tenant: no hay histórico de prueba que
        // valga la pena preservar (feature local, recién agregada), así que se resetea limpio.
        DB::table('claude_usage_counters')->truncate();

        Schema::table('claude_usage_counters', function (Blueprint $table) {
            $table->dropColumn('usuario_id');
            $table->foreignId('tenant_id')->after('id')->constrained('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::table('claude_usage_counters', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'periodo']);
            $table->dropConstrainedForeignId('tenant_id');
        });

        DB::table('claude_usage_counters')->truncate();

        Schema::table('claude_usage_counters', function (Blueprint $table) {
            $table->foreignId('usuario_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->unique(['usuario_id', 'periodo']);
        });
    }
};
