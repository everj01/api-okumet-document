<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateConfiguracionRequest;
use App\Http\Resources\ConfiguracionResource;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function show(Request $request): ConfiguracionResource
    {
        return new ConfiguracionResource($request->user()->tenant);
    }

    public function update(UpdateConfiguracionRequest $request): ConfiguracionResource
    {
        $tenant = $request->user()->tenant;

        $tenant->update([
            'configuracion' => [
                ...($tenant->configuracion ?? []),
                'correos_cc' => $request->validated('correos_cc'),
            ],
        ]);

        return new ConfiguracionResource($tenant);
    }
}
