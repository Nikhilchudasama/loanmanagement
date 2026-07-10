<?php

declare(strict_types=1);

namespace App\Domains\Report\Controllers;

use App\Domains\Report\Services\ReportService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Reports
 *
 * APIs for generating reports
 */

class ReportController
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->reportService->loanReport($request, (int) $request->per_page)
        );
    }

    public function export(Request $request): JsonResponse
    {
        return $this->success(
            $this->reportService->exportLoans($request)
        );
    }

    public function exportPdf(Request $request): JsonResponse
    {
        return $this->success(
            $this->reportService->exportLoansPdf($request)
        );
    }
}
