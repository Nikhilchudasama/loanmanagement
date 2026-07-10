<?php

use App\Domains\Tenant\Models\Tenant;
use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

expect()->extend('toBeOne', fn() => $this->toBe(1));

function createTenantAndUser(string $role = 'admin', string $email = 'admin@test.com'): User
{
    $tenant = Tenant::create([
        'company_name' => 'Test Corp',
        'timezone' => 'UTC',
        'default_currency' => 'USD',
        'status' => 'active',
    ]);

    $spatieRole = match ($role) {
        'super_admin' => 'super-admin',
        'loan_officer' => 'loan-officer',
        default => $role,
    };

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Test User',
        'email' => $email,
        'password' => bcrypt('password'),
        'status' => 'active',
    ]);
    $user->assignRole($spatieRole);

    return $user;
}
