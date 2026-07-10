<?php

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Payment\Gateways\PaymentGatewayInterface;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\PaymentService;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);

    $this->user = createTenantAndUser('admin', 'admin@test.com');
    $this->actingAs($this->user);

    $this->borrower = Borrower::create([
        'tenant_id' => $this->user->tenant_id,
        'full_name' => 'Webhook Test',
        'email' => 'webhook@test.com',
        'mobile_number' => '+1234567890',
    ]);
});

it('processes a completed payment webhook', function (): void {
    $loan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-WH001',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_number' => 1,
        'due_date' => '2026-08-01',
        'principal_amount' => 8000,
        'interest_amount' => 417,
        'total_amount' => 8417,
        'outstanding_balance' => 42000,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_schedule_id' => $emiSchedule->id,
        'borrower_id' => $this->borrower->id,
        'transaction_id' => 'TXN-WH001',
        'gateway_payment_id' => 'cs_test_webhook_completed',
        'gateway' => 'stripe',
        'amount' => 8417,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_type' => 'full',
    ]);

    $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
    $gatewayMock->shouldReceive('processWebhook')
        ->once()
        ->andReturn([
            'event' => 'checkout.session.completed',
            'gateway_payment_id' => 'cs_test_webhook_completed',
            'status' => 'completed',
            'amount' => 8417,
            'currency' => 'usd',
        ]);

    $service = $this->app->make(PaymentService::class);
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('gateway');
    $property->setAccessible(true);
    $property->setValue($service, $gatewayMock);

    $request = Request::create('/api/payments/webhook', 'POST', [], [], [], [], json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => 'cs_test_webhook_completed']],
    ]));

    $service->handleWebhook($request);

    expect($payment->fresh())->status->toBe('completed');
    expect($payment->fresh()->paid_at)->not->toBeNull();

    expect($emiSchedule->fresh()->status)->toBe('paid');
    expect((float) $emiSchedule->fresh()->paid_amount)->toBe(8417.0);

    $this->assertDatabaseHas('payment_gateway_logs', [
        'gateway' => 'stripe',
        'event_type' => 'checkout.session.completed',
    ]);
});

it('updates emi schedule partially on partial payment webhook', function (): void {
    $loan = Loan::create([
        'tenant_id' => $this->user->tenant_id,
        'borrower_id' => $this->borrower->id,
        'loan_number' => 'LN-WH002',
        'loan_type' => 'personal',
        'loan_amount' => 50000,
        'interest_rate' => 10,
        'loan_tenure_months' => 6,
        'emi_start_date' => '2026-08-01',
        'interest_type' => 'reducing',
        'status' => 'active',
        'currency' => 'USD',
    ]);

    $emiSchedule = EmiSchedule::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_number' => 1,
        'due_date' => '2026-08-01',
        'principal_amount' => 8000,
        'interest_amount' => 417,
        'total_amount' => 8417,
        'outstanding_balance' => 42000,
        'status' => 'pending',
    ]);

    $payment = Payment::create([
        'tenant_id' => $this->user->tenant_id,
        'loan_id' => $loan->id,
        'emi_schedule_id' => $emiSchedule->id,
        'borrower_id' => $this->borrower->id,
        'transaction_id' => 'TXN-WH002',
        'gateway_payment_id' => 'cs_test_webhook_partial',
        'gateway' => 'stripe',
        'amount' => 8417,
        'paid_amount' => 5000,
        'remaining_balance' => 3417,
        'currency' => 'USD',
        'status' => 'pending',
        'payment_type' => 'partial',
    ]);

    $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
    $gatewayMock->shouldReceive('processWebhook')
        ->once()
        ->andReturn([
            'event' => 'checkout.session.completed',
            'gateway_payment_id' => 'cs_test_webhook_partial',
            'status' => 'completed',
            'amount' => 5000,
            'currency' => 'usd',
        ]);

    $service = $this->app->make(PaymentService::class);
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('gateway');
    $property->setAccessible(true);
    $property->setValue($service, $gatewayMock);

    $request = Request::create('/api/payments/webhook', 'POST', [], [], [], [], json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => 'cs_test_webhook_partial']],
    ]));

    $service->handleWebhook($request);

    expect($payment->fresh()->status)->toBe('completed');

    expect((float) $emiSchedule->fresh()->paid_amount)->toBe(5000.0);
    expect($emiSchedule->fresh()->status)->toBe('pending');
});

it('skips processing for failed webhook events', function (): void {
    $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
    $gatewayMock->shouldReceive('processWebhook')
        ->once()
        ->andReturn([
            'event' => 'payment_intent.payment_failed',
            'gateway_payment_id' => null,
            'status' => 'failed',
            'error' => 'Payment failed',
        ]);

    $service = $this->app->make(PaymentService::class);
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('gateway');
    $property->setAccessible(true);
    $property->setValue($service, $gatewayMock);

    $request = Request::create('/api/payments/webhook', 'POST', [], [], [], [], json_encode([
        'type' => 'payment_intent.payment_failed',
    ]));

    $service->handleWebhook($request);

    $this->assertDatabaseHas('payment_gateway_logs', [
        'gateway' => 'stripe',
        'event_type' => 'payment_intent.payment_failed',
    ]);
});

it('handles unmatched gateway payment id gracefully', function (): void {
    $gatewayMock = Mockery::mock(PaymentGatewayInterface::class);
    $gatewayMock->shouldReceive('processWebhook')
        ->once()
        ->andReturn([
            'event' => 'checkout.session.completed',
            'gateway_payment_id' => 'cs_test_nonexistent',
            'status' => 'completed',
            'amount' => 1000,
            'currency' => 'usd',
        ]);

    $service = $this->app->make(PaymentService::class);
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('gateway');
    $property->setAccessible(true);
    $property->setValue($service, $gatewayMock);

    $request = Request::create('/api/payments/webhook', 'POST', [], [], [], [], json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => 'cs_test_nonexistent']],
    ]));

    $service->handleWebhook($request);

    $this->assertDatabaseHas('payment_gateway_logs', [
        'gateway' => 'stripe',
        'event_type' => 'checkout.session.completed',
    ]);
});
