<?php

namespace App\Domains\ActivityLog\Controllers;

use App\Domains\ActivityLog\Services\ActivityLogService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Activity Log
 *
 * APIs for activity logs
 */

class ActivityLogController
{
    use ApiResponse;

    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $logs = $this->activityLogService->list((int) $request->per_page);

        return $this->success($logs);
    }
}
