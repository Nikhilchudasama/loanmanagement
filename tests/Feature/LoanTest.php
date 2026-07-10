<?php

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\Loan;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->user = createTenantAndUser('admin', 'admin@test.com');
    $this->user->givePermissionTo('approve-loans');

    $this->borrower = Borrower::create([
        'tenant_id' => $this->user->tenant_id,
        'full_name' => 'Test Borrower',
        'email' => 'borrower@test.com',
        'mobile_number' => '+1234567890',
    ]);

    $this->actingAs($this->user);
});

it('rejects foreclosure without foreclose-loans permission', function (): void {
    $this->user->syncRoles([]);
    $this->user->givePermissionTo(['approve-loans', 'create-loans', 'view-loans']);

    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $this->postJson("/api/loans/{$loan['id']}/approve");

    $response = $this->postJson("/api/loans/{$loan['id']}/foreclose");
    $response->assertStatus(403);
});

it('forecloses an active loan', function (): void {
    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $this->postJson("/api/loans/{$loan['id']}/approve");

    $response = $this->postJson("/api/loans/{$loan['id']}/foreclose");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['status' => 'closed'],
        ]);

    expect($response->json('data.foreclosed_at'))->not->toBeNull();
});

it('rejects foreclosure on non-active loan', function (): void {
    $this->user->givePermissionTo('foreclose-loans');

    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $response = $this->postJson("/api/loans/{$loan['id']}/foreclose");

    $response->assertStatus(422);
});

it('creates a loan', function (): void {
    $response = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => ['loan_number', 'status'],
        ]);

    expect($response->json('data.status'))->toEqual('pending');
});

it('lists loans', function (): void {
    Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-LIST001',
        'loan_type' => 'personal',
        'loan_amount' => 25000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $response = $this->getJson('/api/loans');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'meta', 'links'],
        ]);
});

it('shows a loan', function (): void {
    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 30000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $response = $this->getJson("/api/loans/{$loan['id']}");

    $response->assertStatus(200);
});

it('updates a loan', function (): void {
    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 40000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $response = $this->putJson("/api/loans/{$loan['id']}", [
        'loan_amount' => 45000,
        'notes' => 'Updated amount',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['loan_amount' => 45000],
        ]);
});

it('deletes a loan', function (): void {
    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 20000,
        'interest_rate' => 10,
        'loan_tenure_months' => 3,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $response = $this->deleteJson("/api/loans/{$loan['id']}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});

it('approves a loan and generates emi schedule', function (): void {
    $loanResponse = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_type' => 'personal',
        'loan_amount' => 100000,
        'interest_rate' => 12,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ]);

    $loanId = $loanResponse->json('data.id');
    $response = $this->postJson("/api/loans/{$loanId}/approve");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['status' => 'active'],
        ]);

    expect($response->json('data.emi_schedules'))->toHaveCount(6);
    expect($response->json('data.emi_schedules.0.status'))->toEqual('pending');
});

it('cannot approve an already approved loan', function (): void {
    $loanResponse = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ]);

    $loanId = $loanResponse->json('data.id');
    $this->postJson("/api/loans/{$loanId}/approve");

    $response = $this->postJson("/api/loans/{$loanId}/approve");
    $response->assertStatus(422);
});

it('views emi schedule for a loan', function (): void {
    $loanResponse = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_amount' => 60000,
        'interest_rate' => 10,
        'loan_tenure_months' => 3,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ]);

    $loanId = $loanResponse->json('data.id');
    $this->postJson("/api/loans/{$loanId}/approve");

    $response = $this->getJson("/api/loans/{$loanId}/emi-schedules");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});
