<?php

namespace App\Domains\Tenant\Controllers;

use App\Domains\Tenant\Models\Tenant;
use App\Domains\Tenant\Requests\StoreTenantRequest;
use App\Domains\Tenant\Requests\UpdateTenantRequest;
use App\Domains\Tenant\Services\TenantService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group Tenant Management
 *
 * APIs for managing tenants
 */

class TenantController
{
    use ApiResponse;

    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            $this->tenantService->all()
        );
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantService->create($request->validated());

        return $this->success($tenant, 'Tenant created successfully.', 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return $this->success($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->update($tenant, $request->validated());

        return $this->success($tenant, 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $this->tenantService->delete($tenant);

        return $this->success(message: 'Tenant deleted successfully.');
    }
}
