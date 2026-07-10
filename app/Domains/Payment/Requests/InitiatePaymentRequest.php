<?php

declare(strict_types=1);

namespace App\Domains\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'loan_id' => ['required', Rule::exists('loans', 'id')->where('tenant_id', $tenantId)],
            'emi_schedule_id' => ['nullable', Rule::exists('emi_schedules', 'id')],
            'payment_type' => ['nullable', 'string', 'in:full,partial'],
            'amount' => [
                'nullable',
                'numeric',
                'min:0.01',
                Rule::requiredIf(fn () => $this->payment_type === 'partial'),
            ],
        ];
    }
}
