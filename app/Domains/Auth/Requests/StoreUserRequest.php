<?php

declare(strict_types=1);

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return true;
        }

        return $user && (bool) $user->tenant_id;
    }

    public function rules(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        return [
            'tenant_id' => [
                Rule::when($isSuperAdmin, ['required', 'exists:tenants,id']),
                Rule::when(! $isSuperAdmin, ['prohibited']),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['admin', 'loan-officer', 'borrower'])],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }
}
