<?php

declare(strict_types=1);

namespace App\Domains\Tenant\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'payment_gateway_config' => ['nullable', 'json'],
            'email_config' => ['nullable', 'json'],
            'notification_settings' => ['nullable', 'json'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
