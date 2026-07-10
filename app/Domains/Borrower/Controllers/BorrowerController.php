<?php

namespace App\Domains\Borrower\Controllers;

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Borrower\Requests\StoreBorrowerRequest;
use App\Domains\Borrower\Requests\UpdateBorrowerRequest;
use App\Domains\Borrower\Resources\BorrowerResource;
use App\Domains\Borrower\Services\BorrowerService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Borrower Management
 *
 * APIs for managing borrowers
 */

class BorrowerController
{
    use ApiResponse;

    public function __construct(
        protected BorrowerService $borrowerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->borrowerService->list((int) $request->per_page)
        );
    }

    public function store(StoreBorrowerRequest $request): JsonResponse
    {
        $borrower = $this->borrowerService->create($request->validated());

        return $this->success(BorrowerResource::make($borrower), 'Borrower created successfully.', 201);
    }

    public function show(Borrower $borrower): JsonResponse
    {
        return $this->success(BorrowerResource::make($borrower));
    }

    public function update(UpdateBorrowerRequest $request, Borrower $borrower): JsonResponse
    {
        $borrower = $this->borrowerService->update($borrower, $request->validated());

        return $this->success(BorrowerResource::make($borrower), 'Borrower updated successfully.');
    }

    public function destroy(Borrower $borrower): JsonResponse
    {
        $this->borrowerService->delete($borrower);

        return $this->success(message: 'Borrower deleted successfully.');
    }
}
