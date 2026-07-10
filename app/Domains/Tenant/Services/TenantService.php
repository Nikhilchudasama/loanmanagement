<?php

namespace App\Domains\Tenant\Services;

use App\Domains\Tenant\Models\Tenant;

class TenantService
{
    protected ?Tenant $currentTenant = null;

    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function getTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Tenant::all();
    }

    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant;
    }

    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }
}
