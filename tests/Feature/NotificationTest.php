<?php

use App\Domains\Auth\Models\User;
use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Notification\Jobs\SendEmiReminderJob;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->user = createTenantAndUser('admin', 'admin@test.com');
    $this->user->givePermissionTo('approve-loans');
    $this->actingAs($this->user);

    $this->borrower = Borrower::create([
        'tenant_id' => $this->user->tenant_id,
        'full_name' => 'Test Borrower',
        'email' => 'borrower@test.com',
        'mobile_number' => '+1234567890',
    ]);
});

it('creates database notifications on loan approval', function (): void {
    Mail::fake();

    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $this->postJson("/api/loans/{$loan['id']}/approve");

    $this->assertDatabaseHas('notifications', [
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
    ]);
});

it('lists notifications', function (): void {
    $this->user->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
        'data' => ['message' => 'Test', 'loan_number' => 'LN-001'],
    ]);

    $response = $this->getJson('/api/notifications');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'meta', 'links'],
        ]);
});

it('returns unread notification count', function (): void {
    $this->user->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
        'data' => ['message' => 'Test', 'loan_number' => 'LN-001'],
    ]);

    $response = $this->getJson('/api/notifications/unread-count');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['count' => 1],
        ]);
});

it('marks a notification as read', function (): void {
    $notification = $this->user->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
        'data' => ['message' => 'Test'],
    ]);

    $response = $this->postJson("/api/notifications/{$notification->id}/read");

    $response->assertStatus(200);
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function (): void {
    $this->user->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
        'data' => ['message' => 'Test 1'],
    ]);

    $this->user->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => \App\Domains\Notification\Notifications\PaymentReceivedNotification::class,
        'data' => ['message' => 'Test 2'],
    ]);

    $response = $this->postJson('/api/notifications/read-all');

    $response->assertStatus(200);
    expect($this->user->unreadNotifications()->count())->toEqual(0);
});

it('handles loan approval gracefully when borrower has no email', function (): void {
    Mail::fake();
    $this->borrower->update(['email' => null]);

    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $this->postJson("/api/loans/{$loan['id']}/approve");

    $this->assertDatabaseHas('notifications', [
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
    ]);
});

it('handles loan approval gracefully when borrower is soft deleted', function (): void {
    Mail::fake();
    $this->borrower->delete();

    $loan = $this->postJson('/api/loans', [
        'borrower_id' => $this->borrower->id,
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
    ])->json('data');

    $this->postJson("/api/loans/{$loan['id']}/approve");

    $this->assertDatabaseHas('notifications', [
        'type' => \App\Domains\Notification\Notifications\LoanApprovedNotification::class,
    ]);
});

it('sends emi reminders via command', function (): void {
    Mail::fake();

    $loan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-EMI001',
        'loan_type' => 'personal',
        'loan_amount' => 100000,
        'interest_rate' => 10,
        'loan_tenure_months' => 12,
        'emi_start_date' => '2026-06-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_number' => 1,
        'due_date' => now()->addDays(3)->format('Y-m-d'),
        'principal_amount' => 8000,
        'interest_amount' => 833,
        'total_amount' => 8833,
        'outstanding_balance' => 92000,
        'status' => 'pending',
    ]);

    $this->artisan('emi:send-reminders --days=3')
        ->assertExitCode(0);
});

it('dispatches emi due notification via SendEmiReminderJob', function (): void {
    Mail::fake();

    $loan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-JOB001',
        'loan_type' => 'personal',
        'loan_amount' => 100000,
        'interest_rate' => 10,
        'loan_tenure_months' => 12,
        'emi_start_date' => '2026-06-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_number' => 1,
        'due_date' => now()->addDays(3)->format('Y-m-d'),
        'principal_amount' => 8000,
        'interest_amount' => 833,
        'total_amount' => 8833,
        'outstanding_balance' => 92000,
        'status' => 'pending',
    ]);

    SendEmiReminderJob::dispatchSync($emiSchedule);

    $this->assertDatabaseHas('notifications', [
        'type' => \App\Domains\Notification\Notifications\EmiDueNotification::class,
    ]);
});

it('handles SendEmiReminderJob when borrower is soft deleted', function (): void {
    Mail::fake();

    $loan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-JOB002',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-06-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $this->borrower->delete();

    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_number' => 1,
        'due_date' => now()->addDays(3)->format('Y-m-d'),
        'principal_amount' => 8000,
        'interest_amount' => 833,
        'total_amount' => 8833,
        'outstanding_balance' => 92000,
        'status' => 'pending',
    ]);

    SendEmiReminderJob::dispatchSync($emiSchedule);

    $this->assertDatabaseHas('notifications', [
        'type' => \App\Domains\Notification\Notifications\EmiDueNotification::class,
    ]);
});
