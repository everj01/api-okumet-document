<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Etiqueta extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = ['nombre', 'color'];

    public function documentos(): BelongsToMany
    {
        return $this->belongsToMany(Documento::class);
    }
}
