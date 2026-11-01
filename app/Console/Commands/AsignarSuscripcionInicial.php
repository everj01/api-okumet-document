<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\Tenant;
use Illuminate\Console\Command;

// Migración de datos puntual: los tenants de prueba que existían antes de planes/suscripciones
// no tienen ninguna fila en 'suscripciones'. Este comando les asigna el plan Free igual que si
// se acabaran de registrar hoy. No es parte de un flujo de producto (eso ya lo hace el registro).
class AsignarSuscripcionInicial extends Command
{
    protected $signature = 'okd:suscripciones-iniciales';

    protected $description = 'Asigna el plan Free (30 días desde hoy) a los tenants que todavía no tienen ninguna suscripción';

    public function handle(): int
    {
        $free = Plan::where('nombre', 'Free')->first();

        if (! $free) {
            $this->error('No existe el plan "Free". Corre primero el seeder de planes: php artisan db:seed --class=PlanSeeder');

            return self::FAILURE;
        }

        $tenants = Tenant::whereDoesntHave('suscripciones')->get();

        foreach ($tenants as $tenant) {
            Suscripcion::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $free->id,
                'estado' => 'activa',
                'inicia_en' => now()->toDateString(),
                'termina_en' => now()->addDays(30)->toDateString(),
            ]);
        }

        $this->info("Suscripción Free asignada a {$tenants->count()} tenant(s).");

        return self::SUCCESS;
    }
}
