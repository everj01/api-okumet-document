<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // 2 GB por defecto para todos los negocios por ahora (decisión de producto, sin planes/suscripciones
    // activos todavía); null sigue siendo válido para representar "sin límite" si se necesita a mano.
    private const LIMITE_MB_POR_DEFECTO = 2048;

    public function up(): void
    {
        $tabla = DB::getTablePrefix().'tenants';

        DB::statement("ALTER TABLE {$tabla} ALTER COLUMN limite_almacenamiento_mb SET DEFAULT ".self::LIMITE_MB_POR_DEFECTO);

        DB::table('tenants')
            ->whereNull('limite_almacenamiento_mb')
            ->update(['limite_almacenamiento_mb' => self::LIMITE_MB_POR_DEFECTO]);
    }

    public function down(): void
    {
        $tabla = DB::getTablePrefix().'tenants';

        DB::statement("ALTER TABLE {$tabla} ALTER COLUMN limite_almacenamiento_mb DROP DEFAULT");
    }
};
