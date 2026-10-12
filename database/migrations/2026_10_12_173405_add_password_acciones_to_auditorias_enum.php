<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VIEJAS = "'login', 'login_fallido', 'logout', 'creado', 'actualizado', 'eliminado', 'exportacion', 'reporte', 'super_admin'";

    private const NUEVAS = self::VIEJAS.", 'password_olvidada', 'password_restablecida'";

    public function up(): void
    {
        $tabla = DB::getTablePrefix().'auditorias';

        DB::statement("ALTER TABLE {$tabla} MODIFY COLUMN accion ENUM(".self::NUEVAS.') NOT NULL');
    }

    public function down(): void
    {
        $tabla = DB::getTablePrefix().'auditorias';

        DB::statement("ALTER TABLE {$tabla} MODIFY COLUMN accion ENUM(".self::VIEJAS.') NOT NULL');
    }
};
