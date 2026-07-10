<?php

declare(strict_types=1);

namespace App\Domains\Loan\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'borrower_id' => ['required', Rule::exists('borrowers', 'id')->where('tenant_id', $tenantId)],
            'loan_type' => ['nullable', 'string', 'max:50'],
            'loan_amount' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'loan_tenure_months' => ['required', 'integer', 'min:1', 'max:120'],
            'emi_start_date' => ['required', 'date', 'after_or_equal:today'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', Rule::in(['INR', 'USD', 'EUR', 'AED'])],
            'interest_type' => ['nullable', 'string', 'in:flat,reducing'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
