<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            // Correo tal como se escribió en el intento: lo único que queda de un login fallido sin user_id.
            $table->string('email', 150)->nullable();
            $table->enum('evento', [
                'login_exitoso', 'login_fallido', 'logout',
                'verificacion_pendiente_mostrada', 'codigo_reenviado',
            ]);
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('navegador', 60)->nullable();
            $table->string('sistema_operativo', 60)->nullable();
            $table->string('pais', 100)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->index(['user_id', 'evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_log');
    }
};
