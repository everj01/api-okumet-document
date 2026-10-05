<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->uuid('uuid')->nullable()->unique()->after('tenant_id');
        });

        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->unique(['tenant_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'codigo']);
            $table->unique('codigo');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('uuid');
        });
    }
};
