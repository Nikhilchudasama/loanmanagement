<?php

use App\Domains\ActivityLog\Models\ActivityLog;
use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\Loan;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->user = createTenantAndUser('admin', 'admin@test.com');
    $this->actingAs($this->user);
});

it('lists activity logs', function (): void {
    ActivityLog::create([
        'tenant_id' => $this->user->tenant_id,
        'user_id' => $this->user->id,
        'description' => 'Test log entry',
        'event' => 'created',
        'log_name' => 'default',
    ]);

    $response = $this->getJson('/api/activity-logs');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['current_page', 'data'],
        ]);
});

it('auto-logs when a borrower is created', function (): void {
    $this->postJson('/api/borrowers', [
        'full_name' => 'Log Test',
        'email' => 'log@test.com',
        'mobile_number' => '+1234567890',
    ]);

    expect(ActivityLog::where('event', 'created')
        ->where('subject_type', Borrower::class)
        ->exists()
    )->toBeTrue();
});

it('auto-logs when a borrower is updated', function (): void {
    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Update Log',
        'email' => 'updatelog@test.com',
        'mobile_number' => '+1111111111',
    ])->json('data');

    $this->putJson("/api/borrowers/{$borrower['id']}", [
        'full_name' => 'Updated Name',
    ]);

    expect(ActivityLog::where('event', 'updated')
        ->where('subject_type', Borrower::class)
        ->where('subject_id', $borrower['id'])
        ->exists()
    )->toBeTrue();
});

it('auto-logs when a borrower is deleted', function (): void {
    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Delete Log',
        'email' => 'deletelog@test.com',
        'mobile_number' => '+1222222222',
    ])->json('data');

    $this->deleteJson("/api/borrowers/{$borrower['id']}");

    expect(ActivityLog::where('event', 'deleted')
        ->where('subject_type', Borrower::class)
        ->where('subject_id', $borrower['id'])
        ->exists()
    )->toBeTrue();
});

it('auto-logs when a borrower is restored from soft delete', function (): void {
    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Restore Test',
        'email' => 'restore@test.com',
        'mobile_number' => '+1444444444',
    ])->json('data');

    $this->deleteJson("/api/borrowers/{$borrower['id']}");
    $this->borrowerModel = \App\Domains\Borrower\Models\Borrower::withTrashed()->find($borrower['id']);
    $this->borrowerModel->restore();

    expect(ActivityLog::where('event', 'restored')
        ->where('subject_type', \App\Domains\Borrower\Models\Borrower::class)
        ->where('subject_id', $borrower['id'])
        ->exists()
    )->toBeTrue();
});

it('skips ip and user agent when running in console context', function (): void {
    $borrower = \App\Domains\Borrower\Models\Borrower::create([
        'tenant_id' => $this->user->tenant_id,
        'full_name' => 'Console Test',
        'email' => 'console@test.com',
        'mobile_number' => '+1555555555',
    ]);

    $log = ActivityLog::where('subject_type', \App\Domains\Borrower\Models\Borrower::class)
        ->where('subject_id', $borrower->id)
        ->first();

    expect($log)->not->toBeNull();
});

it('logs loan approval', function (): void {
    $this->user->givePermissionTo('approve-loans');

    $borrower = $this->postJson('/api/borrowers', [
        'full_name' => 'Approval Test',
        'email' => 'approval@test.com',
        'mobile_number' => '+1333333333',
    ])->json('data');

    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $borrower['id'],
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $this->postJson("/api/loans/{$loan['id']}/approve");

    expect(ActivityLog::where('event', 'approved')
        ->where('subject_type', Loan::class)
        ->where('subject_id', $loan['id'])
        ->exists()
    )->toBeTrue();
});
