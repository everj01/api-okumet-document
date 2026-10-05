<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// Filtra por el tenant del usuario autenticado; sin usuario (consola) o con un SuperAdmin, no filtra a propósito.
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $usuario = auth()->user();

        if ($usuario instanceof User) {
            $builder->where($model->getTable().'.tenant_id', $usuario->tenant_id);
        }
    }
}
