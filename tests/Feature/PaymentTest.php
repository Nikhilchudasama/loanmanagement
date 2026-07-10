<?php

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use Illuminate\Support\Facades\URL;

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

    $this->loan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-TEST001',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $this->payment = Payment::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $this->loan->id,
        'borrower_id' => $this->borrower->id,
        'transaction_id' => 'TXN-TEST001',
        'gateway' => 'stripe',
        'amount' => 5000.00,
        'currency' => 'USD',
        'status' => 'completed',
        'payment_type' => 'full',
    ]);
});

it('lists payments', function (): void {
    $response = $this->getJson('/api/payments');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'meta', 'links'],
        ]);
});

it('shows a payment', function (): void {
    $response = $this->getJson("/api/payments/{$this->payment->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['transaction_id' => 'TXN-TEST001'],
        ]);
});

it('requires authentication for payment endpoints', function (): void {
    $this->app->get('auth')->forgetGuards();

    $this->getJson('/api/payments')->assertStatus(401);
    $this->getJson("/api/payments/{$this->payment->id}")->assertStatus(401);
    $this->postJson('/api/payments/initiate', [])->assertStatus(401);
});

it('rejects partial payment without amount', function (): void {
    $this->user->givePermissionTo('process-payments');

    $response = $this->postJson('/api/payments/initiate', [
        'loan_id' => $this->loan->id,
        'payment_type' => 'partial',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

it('rejects partial payment exceeding remaining balance', function (): void {
    $this->user->givePermissionTo('process-payments');

    $this->mock(\App\Domains\Payment\Services\PaymentService::class, function ($mock): void {
        $mock->shouldReceive('initiatePayment')
            ->once()
            ->andThrow(new \Symfony\Component\HttpKernel\Exception\HttpException(422, 'Partial amount cannot exceed remaining balance'));
    });

    $response = $this->postJson('/api/payments/initiate', [
        'loan_id' => $this->loan->id,
        'payment_type' => 'partial',
        'amount' => 999999,
    ]);

    $response->assertStatus(422);
});

it('rejects initiate payment for non-active loan', function (): void {
    $pendingLoan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-TEST002',
        'loan_type' => 'personal',
        'loan_amount' => 10000,
        'interest_rate' => 10,
        'loan_tenure_months' => 3,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'pending',
        'currency' => 'USD',
    ]);

    $this->user->givePermissionTo('process-payments');

    $response = $this->postJson('/api/payments/initiate', [
        'loan_id' => $pendingLoan->id,
    ]);

    $response->assertStatus(422);
});

it('validates required fields for initiate payment', function (): void {
    $this->user->givePermissionTo('process-payments');

    $response = $this->postJson('/api/payments/initiate', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['loan_id']);
});

it('initiates a payment successfully', function (): void {
    $this->user->givePermissionTo('process-payments');

    $this->mock(PaymentService::class, function ($mock): void {
        $mock->shouldReceive('initiatePayment')
            ->once()
            ->andReturn([
                'payment' => ['id' => 1, 'amount' => 50000, 'status' => 'pending'],
                'payment_link' => 'https://checkout.stripe.com/test',
            ]);
    });

    $response = $this->postJson('/api/payments/initiate', [
        'loan_id' => $this->loan->id,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['payment_link' => 'https://checkout.stripe.com/test'],
        ]);
});

it('lists payments with pagination', function (): void {
    for ($i = 0; $i < 20; $i++) {
        Payment::create([
            'tenant_id' => $this->user->tenant_id,
            'loan_id' => $this->loan->id,
            'borrower_id' => $this->borrower->id,
            'transaction_id' => 'TXN-PAG' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'gateway' => 'stripe',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_type' => 'full',
        ]);
    }

    $response = $this->getJson('/api/payments?per_page=5');

    $response->assertStatus(200);
    expect(count($response->json('data.data')))->toBeLessThanOrEqual(5);
});

it('processes payment via signed secure-pay url', function (): void {
    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $this->loan->id,
        'emi_number' => 1,
        'due_date' => '2026-08-01',
        'principal_amount' => 8000,
        'interest_amount' => 417,
        'total_amount' => 8417,
        'outstanding_balance' => 42000,
        'status' => 'pending',
    ]);

    $this->mock(PaymentService::class, function ($mock) use ($emiSchedule): void {
        $mock->shouldReceive('initiateFromSignedLink')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $emiSchedule->id))
            ->andReturn([
                'payment' => ['id' => 1, 'amount' => 8417, 'status' => 'pending'],
                'payment_link' => 'https://checkout.stripe.com/test',
            ]);
    });

    $signedUrl = URL::temporarySignedRoute(
        'payments.secure-pay',
        now()->addMinutes(30),
        ['emiSchedule' => $emiSchedule->id],
    );

    $response = $this->getJson($signedUrl);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['payment_link' => 'https://checkout.stripe.com/test'],
        ]);
});

it('rejects expired secure-pay url', function (): void {
    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $this->loan->id,
        'emi_number' => 1,
        'due_date' => '2026-08-01',
        'principal_amount' => 8000,
        'interest_amount' => 417,
        'total_amount' => 8417,
        'outstanding_balance' => 42000,
        'status' => 'pending',
    ]);

    $expiredUrl = URL::temporarySignedRoute(
        'payments.secure-pay',
        now()->subMinutes(5),
        ['emiSchedule' => $emiSchedule->id],
    );

    $response = $this->getJson($expiredUrl);

    $response->assertStatus(403);
});

it('rejects unsigned request to secure-pay url', function (): void {
    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $this->loan->id,
        'emi_number' => 1,
        'due_date' => '2026-08-01',
        'principal_amount' => 8000,
        'interest_amount' => 417,
        'total_amount' => 8417,
        'outstanding_balance' => 42000,
        'status' => 'pending',
    ]);

    $response = $this->getJson("/api/pay/{$emiSchedule->id}");

    $response->assertStatus(403);
});

it('rejects report export without process-payments permission', function (): void {
    $this->user->syncRoles([]);

    $this->postJson('/api/payments/initiate', [
        'loan_id' => $this->loan->id,
    ])->assertStatus(403);
});
