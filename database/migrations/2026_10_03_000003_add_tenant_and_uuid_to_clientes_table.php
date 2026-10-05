<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->uuid('uuid')->nullable()->unique()->after('tenant_id');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['numero_documento']);
            $table->unique(['tenant_id', 'numero_documento']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'numero_documento']);
            $table->unique('numero_documento');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('uuid');
        });
    }
};
