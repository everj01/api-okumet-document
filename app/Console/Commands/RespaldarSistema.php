<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RespaldarSistema extends Command
{
    protected $signature = 'okd:respaldo {--solo-bd : Respalda únicamente la base de datos}';

    protected $description = 'Genera una copia de seguridad de la base de datos y de los documentos';

    public function handle(): int
    {
        $carpeta = storage_path('app/respaldos');
        File::ensureDirectoryExists($carpeta);

        $marca = now()->format('Y-m-d_His');
        $archivoSql = "{$carpeta}/bd_{$marca}.sql";

        if (! $this->volcarBaseDeDatos($archivoSql)) {
            $this->error('No se pudo generar el volcado de la base de datos. Revisa que mysqldump esté instalado.');

            return self::FAILURE;
        }

        $this->info("Base de datos: {$archivoSql}");

        if (! $this->option('solo-bd')) {
            $archivoZip = "{$carpeta}/documentos_{$marca}.zip";
            $this->comprimirDocumentos($archivoZip);
            $this->info("Documentos: {$archivoZip}");
        }

        return self::SUCCESS;
    }

    private function volcarBaseDeDatos(string $destino): bool
    {
        $proceso = new Process([
            'mysqldump',
            '--host='.config('database.connections.mysql.host'),
            '--port='.config('database.connections.mysql.port'),
            '--user='.config('database.connections.mysql.username'),
            '--password='.config('database.connections.mysql.password'),
            '--single-transaction',
            config('database.connections.mysql.database'),
        ]);

        $proceso->setTimeout(600);
        $proceso->run();

        if (! $proceso->isSuccessful()) {
            return false;
        }

        File::put($destino, $proceso->getOutput());

        return true;
    }

    private function comprimirDocumentos(string $destino): void
    {
        $origen = storage_path('app/documentos');

        if (! File::isDirectory($origen)) {
            return;
        }

        $zip = new \ZipArchive();
        $zip->open($destino, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach (File::allFiles($origen) as $archivo) {
            $zip->addFile($archivo->getRealPath(), $archivo->getRelativePathname());
        }

        $zip->close();
    }
}
