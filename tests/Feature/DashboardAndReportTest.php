<?php

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\Loan;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->user = createTenantAndUser('admin', 'admin@test.com');
    $this->actingAs($this->user);

    $this->borrower = Borrower::create([
        'tenant_id' => $this->user->tenant_id,
        'full_name' => 'Test Borrower',
        'email' => 'borrower@test.com',
        'mobile_number' => '+1234567890',
    ]);
});

it('returns dashboard metrics', function (): void {
    Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-DASH001',
        'loan_type' => 'personal',
        'loan_amount' => 100000,
        'interest_rate' => 10,
        'loan_tenure_months' => 12,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $response = $this->getJson('/api/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['total_borrowers', 'active_loans', 'outstanding_amount', 'upcoming_emis'],
        ]);
});

it('filters dashboard metrics for tenant user', function (): void {
    $response = $this->getJson('/api/dashboard');

    $response->assertStatus(200);
    expect($response->json('data.total_borrowers'))->toEqual(1);
});

it('returns loan report as super admin', function (): void {
    $superAdmin = \App\Domains\Auth\Models\User::create([
        'name' => 'Super Admin',
        'email' => 'super@test.com',
        'password' => bcrypt('password'),
        'status' => 'active',
    ]);
    $superAdmin->assignRole('super-admin');
    $this->actingAs($superAdmin);

    $response = $this->getJson('/api/reports/loans');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'meta', 'links'],
        ]);
});

it('exports loan report to excel', function (): void {
    $this->user->givePermissionTo('export-reports');

    Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-EXP001',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $response = $this->getJson('/api/reports/loans/export');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['url', 'filename'],
        ]);
    expect($response->json('data.filename'))->toMatch('/^loan-report-\d{4}-\d{2}-\d{2}-\d{6}\.xlsx$/');
});

it('returns loan report filtered by status', function (): void {
    Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-RPT001',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $response = $this->getJson('/api/reports/loans?loan_status=active');

    $response->assertStatus(200);
});

it('exports loan report to pdf', function (): void {
    $this->user->givePermissionTo('export-reports');

    Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-PDF001',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $response = $this->getJson('/api/reports/loans/export-pdf');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['url', 'filename'],
        ]);
    expect($response->json('data.filename'))->toMatch('/^loan-report-\d{4}-\d{2}-\d{2}-\d{6}\.pdf$/');
    Storage::disk('public')->assertExists($response->json('data.filename'));
});

it('rejects report export without export-reports permission', function (): void {
    $this->user->syncRoles([]);

    $this->getJson('/api/reports/loans/export')->assertStatus(403);
    $this->getJson('/api/reports/loans/export-pdf')->assertStatus(403);
});
