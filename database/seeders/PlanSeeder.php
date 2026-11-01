<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            ['nombre' => 'Free', 'descripcion' => 'Plan gratuito para probar la plataforma.', 'precio' => 0, 'limite_usuarios' => 2, 'limite_llamadas_claude' => 50, 'limite_almacenamiento_mb' => 500],
            ['nombre' => 'Inicial', 'descripcion' => 'Para estudios que recién empiezan.', 'precio' => 15, 'limite_usuarios' => 3, 'limite_llamadas_claude' => 100, 'limite_almacenamiento_mb' => 1024],
            ['nombre' => 'Básico', 'descripcion' => 'Para equipos pequeños con más volumen de casos.', 'precio' => 35, 'limite_usuarios' => 6, 'limite_llamadas_claude' => 250, 'limite_almacenamiento_mb' => 5120],
            ['nombre' => 'Pro', 'descripcion' => 'Para estudios en crecimiento.', 'precio' => 59, 'limite_usuarios' => 12, 'limite_llamadas_claude' => 600, 'limite_almacenamiento_mb' => 15360],
            ['nombre' => 'Estudio', 'descripcion' => 'Para estudios grandes con alto volumen de documentos.', 'precio' => 99, 'limite_usuarios' => 25, 'limite_llamadas_claude' => 1300, 'limite_almacenamiento_mb' => 30720],
        ];

        foreach ($planes as $plan) {
            Plan::updateOrCreate(['nombre' => $plan['nombre']], $plan + ['activo' => true]);
        }
    }
}
