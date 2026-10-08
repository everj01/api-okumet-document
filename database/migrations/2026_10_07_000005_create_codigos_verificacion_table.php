<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_verificacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('codigo', 6);
            $table->timestamp('expira_en');
            $table->timestamp('usado_en')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'codigo']);
        });

        // Verificación de correo retroactiva: todos los usuarios existentes quedan sin verificar
        // hasta que vuelvan a iniciar sesión y confirmen el código (decisión del usuario).
        DB::table('users')->update(['email_verified_at' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_verificacion');
    }
};
