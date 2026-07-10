<?php

namespace App\Http\Middleware;

use App\Domains\Tenant\Models\Tenant;
use App\Domains\Tenant\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $tenant = $user->tenant_id
                ? Tenant::find($user->tenant_id)
                : null;

            $this->tenantService->setCurrentTenant($tenant);
        }

        return $next($request);
    }
}
