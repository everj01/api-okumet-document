<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function show(Request $request): TenantResource
    {
        return new TenantResource($request->user()->tenant);
    }

    public function update(UpdateTenantRequest $request): TenantResource
    {
        $tenant = $request->user()->tenant;
        $tenant->update($request->validated());

        return new TenantResource($tenant);
    }

    public function logo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $tenant = $request->user()->tenant;

        if ($tenant->logo_path) {
            Storage::disk('logos')->delete($tenant->logo_path);
        }

        $ruta = $request->file('logo')->store((string) Str::uuid(), 'logos');
        $tenant->update(['logo_path' => $ruta]);

        return response()->json(['logo_url' => Storage::disk('logos')->url($ruta)]);
    }
}
