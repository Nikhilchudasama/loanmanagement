<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Dashboard\Services\DashboardService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group Dashboard
 *
 * APIs for the dashboard
 */

class DashboardController
{
    use ApiResponse;

    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(
            $this->dashboardService->metrics()
        );
    }
}
