<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

// El id autoincrement sigue para relaciones internas; el uuid es la clave que se expone hacia afuera.
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
