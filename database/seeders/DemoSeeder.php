<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Evento;
use App\Models\Expediente;
use App\Models\Rol;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

// Datos de ejemplo para ver el sistema funcionando. Bórralo antes de pasar a producción.
class DemoSeeder extends Seeder
{
    public function run(?Tenant $tenant = null): void
    {
        // Sin usuario autenticado durante el seed: tenant_id se fuerza a mano en cada modelo creado.
        $tenant ??= Tenant::firstOrCreate(['nombre_comercial' => 'Principal']);

        $abogado = User::updateOrCreate(
            ['email' => 'abogado@okd.test'],
            [
                'name' => 'Lucía Ramírez',
                'password' => 'Abogado12345',
                'rol_id' => Rol::where('nombre', Rol::ABOGADO)->value('id'),
                'telefono' => '943 112 233',
                'activo' => true,
            ]
        );
        $abogado->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        $asistente = User::updateOrCreate(
            ['email' => 'asistente@okd.test'],
            [
                'name' => 'Marco Salazar',
                'password' => 'Asistente12345',
                'rol_id' => Rol::where('nombre', Rol::ASISTENTE)->value('id'),
                'activo' => true,
            ]
        );
        $asistente->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        $clientes = [
            [
                'tipo_persona' => 'natural',
                'tipo_documento' => 'DNI',
                'numero_documento' => '45678912',
                'nombre' => 'Rosa Quispe Vargas',
                'email' => 'rosa.quispe@example.com',
                'telefono' => '987 654 321',
                'direccion' => 'Av. Pardo 450, Nuevo Chimbote',
            ],
            [
                'tipo_persona' => 'juridica',
                'tipo_documento' => 'RUC',
                'numero_documento' => '20458796321',
                'nombre' => 'Transportes del Norte S.A.C.',
                'email' => 'contacto@transportesnorte.com',
                'telefono' => '043 321 654',
                'direccion' => 'Jr. Elías Aguirre 120, Chimbote',
            ],
        ];

        foreach ($clientes as $datos) {
            Cliente::updateOrCreate(['numero_documento' => $datos['numero_documento']], $datos)
                ->forceFill(['tenant_id' => $tenant->id])->saveQuietly();
        }

        $rosa = Cliente::where('numero_documento', '45678912')->first();
        $empresa = Cliente::where('numero_documento', '20458796321')->first();

        $expediente = Expediente::updateOrCreate(
            ['codigo' => 'EXP-2026-001'],
            [
                'titulo' => 'Alimentos - Quispe Vargas',
                'cliente_id' => $rosa->id,
                'abogado_id' => $abogado->id,
                'materia' => 'Familia',
                'juzgado' => '1° Juzgado de Paz Letrado de Nuevo Chimbote',
                'estado' => 'en_tramite',
                'fecha_inicio' => now()->subMonths(3)->toDateString(),
                'descripcion' => 'Demanda de alimentos a favor de menor de edad.',
            ]
        );
        $expediente->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        Expediente::updateOrCreate(
            ['codigo' => 'EXP-2026-002'],
            [
                'titulo' => 'Obligación de dar suma de dinero',
                'cliente_id' => $empresa->id,
                'abogado_id' => $abogado->id,
                'materia' => 'Civil',
                'juzgado' => '2° Juzgado Civil del Santa',
                'estado' => 'abierto',
                'fecha_inicio' => now()->subMonth()->toDateString(),
            ]
        )->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        $expediente->movimientos()->firstOrCreate(
            ['titulo' => 'Presentación de demanda'],
            ['fecha' => now()->subMonths(3)->toDateString(), 'detalle' => 'Se presentó la demanda con anexos.']
        )->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        $expediente->movimientos()->firstOrCreate(
            ['titulo' => 'Auto admisorio'],
            ['fecha' => now()->subMonths(2)->toDateString(), 'detalle' => 'El juzgado admitió la demanda.']
        )->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        Evento::firstOrCreate(
            ['titulo' => 'Audiencia única', 'expediente_id' => $expediente->id],
            [
                'tipo' => 'audiencia',
                'inicio' => now()->addDays(6)->setTime(9, 30),
                'lugar' => 'Sede judicial del Santa - Sala 2',
                'responsable_id' => $abogado->id,
                'recordatorio_dias' => 2,
            ]
        )->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        Evento::firstOrCreate(
            ['titulo' => 'Vence plazo para contestar', 'expediente_id' => $expediente->id],
            [
                'tipo' => 'vencimiento',
                'inicio' => now()->addDays(2)->setTime(17, 0),
                'responsable_id' => $abogado->id,
                'recordatorio_dias' => 1,
            ]
        )->forceFill(['tenant_id' => $tenant->id])->saveQuietly();
    }
}
