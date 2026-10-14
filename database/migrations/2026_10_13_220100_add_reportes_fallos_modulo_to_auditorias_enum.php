<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const VIEJAS = "'auth', 'clientes', 'expedientes', 'documentos', 'usuarios', 'eventos', 'exportaciones', 'reportes', 'sistema_admin'";

    private const NUEVAS = self::VIEJAS.", 'reportes_fallos'";

    public function up(): void
    {
        $tabla = DB::getTablePrefix().'auditorias';

        DB::statement("ALTER TABLE {$tabla} MODIFY COLUMN modulo ENUM(".self::NUEVAS.') NOT NULL');
    }

    public function down(): void
    {
        $tabla = DB::getTablePrefix().'auditorias';

        DB::statement("ALTER TABLE {$tabla} MODIFY COLUMN modulo ENUM(".self::VIEJAS.') NOT NULL');
    }
};
