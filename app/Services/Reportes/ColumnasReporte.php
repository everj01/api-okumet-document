<?php

namespace App\Services\Reportes;

// Única fuente de verdad de qué columnas existen por módulo: la usan el endpoint de columnas
// disponibles, la validación del POST, y el job que escribe el xlsx (encabezados + selección).
class ColumnasReporte
{
    public const MODULOS = ['tenants', 'usuarios', 'clientes', 'expedientes', 'eventos', 'logs'];

    private const DEFINICIONES = [
        'tenants' => [
            'nombre_comercial' => 'Nombre comercial',
            'razon_social' => 'Razón social',
            'ruc' => 'RUC',
            'email' => 'Email',
            'telefono' => 'Teléfono',
            'activo' => 'Activo',
            'created_at' => 'Creado',
        ],
        'usuarios' => [
            'name' => 'Nombre',
            'email' => 'Email',
            'rol' => 'Rol',
            'tenant' => 'Negocio',
            'activo' => 'Activo',
            'created_at' => 'Creado',
        ],
        'clientes' => [
            'nombre' => 'Nombre',
            'numero_documento' => 'Documento',
            'tipo_documento' => 'Tipo de documento',
            'email' => 'Email',
            'telefono' => 'Teléfono',
            'tenant' => 'Negocio',
            'created_at' => 'Creado',
        ],
        'expedientes' => [
            'codigo' => 'Código',
            'titulo' => 'Título',
            'materia' => 'Materia',
            'juzgado' => 'Juzgado',
            'estado' => 'Estado',
            'fecha_inicio' => 'Fecha de inicio',
            'tenant' => 'Negocio',
            'cliente' => 'Cliente',
            'created_at' => 'Creado',
        ],
        'eventos' => [
            'titulo' => 'Título',
            'tipo' => 'Tipo',
            'inicio' => 'Inicio',
            'fin' => 'Fin',
            'estado' => 'Estado',
            'tenant' => 'Negocio',
            'created_at' => 'Creado',
        ],
        'logs' => [
            'accion' => 'Acción',
            'modulo' => 'Módulo',
            'actor_nombre' => 'Actor',
            'actor_email' => 'Email del actor',
            'tenant' => 'Negocio',
            'descripcion' => 'Descripción',
            'ip' => 'IP',
            'creado_en' => 'Fecha',
        ],
    ];

    /** @return list<array{clave: string, etiqueta: string}> */
    public static function disponibles(string $modulo): array
    {
        return collect(self::DEFINICIONES[$modulo] ?? [])
            ->map(fn (string $etiqueta, string $clave) => ['clave' => $clave, 'etiqueta' => $etiqueta])
            ->values()
            ->all();
    }

    /** @return list<string> */
    public static function claves(string $modulo): array
    {
        return array_keys(self::DEFINICIONES[$modulo] ?? []);
    }

    public static function etiqueta(string $modulo, string $clave): string
    {
        return self::DEFINICIONES[$modulo][$clave] ?? $clave;
    }
}
