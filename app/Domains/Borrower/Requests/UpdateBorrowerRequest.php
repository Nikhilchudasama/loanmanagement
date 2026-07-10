<?php

declare(strict_types=1);

namespace App\Domains\Borrower\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBorrowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $borrowerId = $this->route('borrower')?->id;

        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('borrowers', 'email')
                ->where('tenant_id', auth()->user()?->tenant_id)
                ->ignore($borrowerId),
            ],
            'mobile_number' => ['sometimes', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:active,inactive,blacklisted'],
        ];
    }
}
