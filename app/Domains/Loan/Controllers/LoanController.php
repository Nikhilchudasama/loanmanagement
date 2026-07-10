<?php

namespace App\Domains\Loan\Controllers;

use App\Domains\Loan\Models\Loan;
use App\Domains\Loan\Requests\StoreLoanRequest;
use App\Domains\Loan\Requests\UpdateLoanRequest;
use App\Domains\Loan\Resources\LoanResource;
use App\Domains\Loan\Services\LoanService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Loan Management
 *
 * APIs for managing loans
 */

class LoanController
{
    use ApiResponse;

    public function __construct(
        protected LoanService $loanService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->loanService->list((int) $request->per_page)
        );
    }

    public function store(StoreLoanRequest $request): JsonResponse
    {
        $loan = $this->loanService->create($request->validated());

        return $this->success(LoanResource::make($loan->load('borrower')), 'Loan created successfully.', 201);
    }

    public function show(Loan $loan): JsonResponse
    {
        return $this->success(
            LoanResource::make($this->loanService->find($loan))
        );
    }

    public function update(UpdateLoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->update($loan, $request->validated());

        return $this->success(LoanResource::make($loan->load('borrower')), 'Loan updated successfully.');
    }

    public function destroy(Loan $loan): JsonResponse
    {
        $this->loanService->delete($loan);

        return $this->success(message: 'Loan deleted successfully.');
    }

    public function approve(Loan $loan): JsonResponse
    {
        $loan = $this->loanService->approve($loan);

        return $this->success(LoanResource::make($loan->load('emiSchedules')), 'Loan approved successfully.');
    }

    public function foreclose(Loan $loan): JsonResponse
    {
        $loan = $this->loanService->foreclose($loan);

        return $this->success(LoanResource::make($loan), 'Loan foreclosed successfully.');
    }
}
