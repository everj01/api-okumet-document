<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TIPOS_ANTERIORES = ['audiencia', 'vencimiento', 'reunion', 'otro'];

    private const TIPOS_NUEVOS = [
        'audiencia', 'vencimiento', 'reunion', 'otro',
        'plazo_absolver', 'plazo_apelar', 'actuacion_prueba',
        'sentencia_1_instancia', 'sentencia_2_instancia',
    ];

    public function up(): void
    {
        $this->cambiarEnum(self::TIPOS_NUEVOS);
    }

    public function down(): void
    {
        // Antes de volver al enum corto, cualquier evento con un tipo nuevo pasa a 'otro' para no romper el down().
        DB::table('eventos')->whereNotIn('tipo', self::TIPOS_ANTERIORES)->update(['tipo' => 'otro']);

        $this->cambiarEnum(self::TIPOS_ANTERIORES);
    }

    private function cambiarEnum(array $tipos): void
    {
        $lista = collect($tipos)->map(fn (string $tipo) => "'{$tipo}'")->implode(',');
        $tabla = DB::getTablePrefix().'eventos';

        DB::statement("ALTER TABLE {$tabla} MODIFY tipo ENUM({$lista}) NOT NULL DEFAULT 'audiencia'");
    }
};
