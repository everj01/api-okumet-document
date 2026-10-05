<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => Rol::ADMIN, 'descripcion' => 'Administra usuarios y toda la información'],
            ['nombre' => Rol::ABOGADO, 'descripcion' => 'Gestiona clientes, expedientes y documentos'],
            ['nombre' => Rol::ASISTENTE, 'descripcion' => 'Registra información y sube documentos'],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(['nombre' => $rol['nombre']], $rol);
        }

        // Sin usuario autenticado durante el seed: el tenant_id se asigna a mano en vez de vía BelongsToTenant.
        $tenant = Tenant::firstOrCreate(['nombre_comercial' => 'Principal']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@okd.test'],
            [
                'name' => 'Administrador',
                'password' => 'Admin12345',
                'rol_id' => Rol::where('nombre', Rol::ADMIN)->value('id'),
                'activo' => true,
            ]
        );
        $admin->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        $this->call(DemoSeeder::class, false, ['tenant' => $tenant]);
    }
}
