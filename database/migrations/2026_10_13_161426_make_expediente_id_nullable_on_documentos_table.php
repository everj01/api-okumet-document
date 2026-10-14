<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Sin doctrine/dbal en el proyecto, ->nullable()->change() no está disponible: se usa SQL crudo.
// La FK (expediente_id -> expedientes.id, cascadeOnDelete) no se toca, solo la nulabilidad de la columna.
return new class extends Migration
{
    public function up(): void
    {
        $tabla = Schema::getConnection()->getTablePrefix().'documentos';

        DB::statement("ALTER TABLE {$tabla} MODIFY expediente_id BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        $tabla = Schema::getConnection()->getTablePrefix().'documentos';

        // Revertir solo es seguro si no quedó ningún documento huérfano (expediente_id NULL).
        DB::statement("ALTER TABLE {$tabla} MODIFY expediente_id BIGINT UNSIGNED NOT NULL");
    }
};
