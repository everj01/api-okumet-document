<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Los datos previos al multitenant quedan bajo un tenant "Principal"; tenant_id y uuid siguen nullable en la base porque los modelos ya los asignan vía BelongsToTenant/HasUuid.
return new class extends Migration
{
    private const TABLAS = ['users', 'clientes', 'expedientes', 'documentos', 'eventos', 'movimientos'];

    public function up(): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'nombre_comercial' => 'Principal',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (self::TABLAS as $tabla) {
            DB::table($tabla)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);

            DB::table($tabla)->whereNull('uuid')->select('id')->orderBy('id')->get()->each(
                fn ($fila) => DB::table($tabla)->where('id', $fila->id)->update(['uuid' => (string) Str::uuid()])
            );
        }
    }

    public function down(): void
    {
        // Datos históricos: no se revierten.
    }
};
