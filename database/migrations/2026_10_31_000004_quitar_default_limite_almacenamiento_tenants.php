<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Con planes/suscripciones activos, el límite de almacenamiento por defecto pasa a venir del plan;
    // la columna en tenants vuelve a ser solo una excepción manual (igual que limite_claude_override).
    private const LIMITE_MB_POR_DEFECTO_ANTERIOR = 2048;

    public function up(): void
    {
        $tabla = DB::getTablePrefix().'tenants';

        DB::statement("ALTER TABLE {$tabla} ALTER COLUMN limite_almacenamiento_mb DROP DEFAULT");

        // Datos de prueba locales: ningún tenant tiene todavía un override manual real seteado a mano,
        // así que es seguro limpiar el valor que dejó el default anterior.
        DB::table('tenants')
            ->where('limite_almacenamiento_mb', self::LIMITE_MB_POR_DEFECTO_ANTERIOR)
            ->update(['limite_almacenamiento_mb' => null]);
    }

    public function down(): void
    {
        $tabla = DB::getTablePrefix().'tenants';

        DB::statement("ALTER TABLE {$tabla} ALTER COLUMN limite_almacenamiento_mb SET DEFAULT ".self::LIMITE_MB_POR_DEFECTO_ANTERIOR);
    }
};
