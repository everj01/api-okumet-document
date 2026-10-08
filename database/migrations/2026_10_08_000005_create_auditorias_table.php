<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Nullable: un login fallido no tiene usuario, y una acción de super admin no pertenece a un tenant.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            // Snapshot del actor en texto: si el usuario se elimina después, el registro de auditoría sigue siendo legible.
            $table->string('actor_nombre', 150)->nullable();
            $table->string('actor_email', 150)->nullable();
            $table->enum('accion', [
                'login', 'login_fallido', 'logout',
                'creado', 'actualizado', 'eliminado',
                'exportacion', 'reporte', 'super_admin',
            ]);
            $table->enum('modulo', [
                'auth', 'clientes', 'expedientes', 'documentos', 'usuarios',
                'eventos', 'exportaciones', 'reportes', 'sistema_admin',
            ]);
            $table->text('descripcion');
            $table->string('ip', 45)->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->index(['tenant_id', 'creado_en']);
            $table->index(['accion', 'modulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
