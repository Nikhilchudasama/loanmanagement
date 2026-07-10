<?php

declare(strict_types=1);

namespace App\Domains\Loan\Requests;

use App\Domains\Loan\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_type' => ['nullable', 'string', 'max:50'],
            'loan_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loan_tenure_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'emi_start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', Rule::in(['INR', 'USD', 'EUR', 'AED'])],
            'interest_type' => ['nullable', 'string', 'in:flat,reducing'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function passedValidation(): void
    {
        $loan = $this->route('loan');

        if ($loan instanceof Loan && in_array($loan->status, ['active', 'approved'], true)) {
            $changed = array_intersect_key(
                $this->validated(),
                array_flip(['loan_amount', 'interest_rate', 'loan_tenure_months', 'emi_start_date', 'interest_type']),
            );

            if ($changed !== []) {
                throw ValidationException::withMessages([
                    'loan_amount' => ['Cannot modify loan terms after the loan has been approved or activated.'],
                ]);
            }
        }
    }
}
