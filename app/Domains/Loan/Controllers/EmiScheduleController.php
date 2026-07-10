<?php

declare(strict_types=1);

namespace App\Domains\Loan\Controllers;

use App\Domains\Loan\Models\Loan;
use App\Domains\Loan\Resources\EmiScheduleResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group EMI Schedule
 *
 * APIs for managing EMI schedules
 */

class EmiScheduleController
{
    use ApiResponse;

    public function index(Loan $loan): JsonResponse
    {
        $schedules = $loan->emiSchedules()->orderBy('emi_number')->get();

        return $this->success(EmiScheduleResource::collection($schedules));
    }
}
