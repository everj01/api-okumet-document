<?php

namespace App\Observers\Auditoria;

use App\Services\RegistradorAuditoria;
use Illuminate\Database\Eloquent\Model;

abstract class AuditoriaObserverBase
{
    public function __construct(protected readonly RegistradorAuditoria $auditor) {}

    abstract protected function modulo(): string;

    abstract protected function etiqueta(Model $modelo): string;

    public function created(Model $modelo): void
    {
        $this->registrar('creado', $modelo, "Creó {$this->etiqueta($modelo)}.");
    }

    public function updated(Model $modelo): void
    {
        $this->registrar('actualizado', $modelo, "Actualizó {$this->etiqueta($modelo)}.");
    }

    public function deleted(Model $modelo): void
    {
        $this->registrar('eliminado', $modelo, "Eliminó {$this->etiqueta($modelo)}.");
    }

    private function registrar(string $accion, Model $modelo, string $descripcion): void
    {
        $this->auditor->registrar($accion, $this->modulo(), $descripcion, tenantId: $modelo->tenant_id ?? null);
    }
}
