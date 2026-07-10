<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\ActivityLog\Services\ActivityLogService;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Payment\Events\PaymentReceived;
use App\Domains\Payment\Gateways\PaymentGatewayInterface;
use App\Domains\Payment\Gateways\StripeGateway;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\PaymentGatewayLog;
use App\Domains\Payment\Resources\PaymentResource;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Tenant\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    protected ?PaymentGatewayInterface $gateway = null;

    public function __construct(
        protected TenantService $tenantService,
        protected ActivityLogService $activityLogService
    ) {}

    public function list(int $perPage = 15): array
    {
        return PaymentResource::collection(
            Payment::with(['loan', 'borrower'])
                ->orderByDesc('created_at')
                ->paginate($perPage)
        )->response()->getData(true);
    }

    public function find(Payment $payment): Payment
    {
        $payment->load(['loan', 'borrower', 'emiSchedule']);

        return $payment;
    }

    public function initiatePayment(Loan $loan, ?int $emiScheduleId = null, string $paymentType = 'full', ?float $amount = null): array
    {
        $tenantId = auth()->user()->tenant_id;

        if (! $tenantId) {
            throw new HttpException(403, 'Super admins cannot initiate payments.');
        }

        if ($loan->status !== 'active') {
            throw new HttpException(422, 'Payments can only be initiated on active loans.');
        }

        $emiSchedule = $emiScheduleId
            ? $loan->emiSchedules()->findOrFail($emiScheduleId)
            : null;

        $remaining = null;

        if ($paymentType === 'partial') {
            if ($amount === null || $amount <= 0) {
                throw new HttpException(422, 'Amount is required for partial payments.');
            }

            $fullAmount = $emiSchedule
                ? $emiSchedule->total_amount
                : $loan->emiSchedules()->where('status', 'pending')->get()->sum(fn ($emi) => $emi->total_amount - ($emi->paid_amount ?? 0));

            $remaining = $emiSchedule
                ? max(0, $emiSchedule->total_amount - ($emiSchedule->paid_amount ?? 0))
                : $fullAmount;

            if ($amount > $remaining) {
                throw new HttpException(422, 'Partial amount cannot exceed remaining balance of ' . $remaining . '.');
            }

            $finalAmount = $amount;
        } else {
            $finalAmount = $emiSchedule
                ? $emiSchedule->total_amount
                : $loan->emiSchedules()->where('status', 'pending')->get()->sum(fn ($emi) => $emi->total_amount - ($emi->paid_amount ?? 0));
        }

        $gateway = $this->getGateway();

        return DB::transaction(function () use ($loan, $tenantId, $emiSchedule, $finalAmount, $paymentType, $remaining, $gateway): array {
            $payment = Payment::create([
                'tenant_id' => $tenantId,
                'loan_id' => $loan->id,
                'emi_schedule_id' => $emiSchedule?->id,
                'borrower_id' => $loan->borrower_id,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                'gateway' => 'stripe',
                'amount' => $finalAmount,
                'paid_amount' => $paymentType === 'partial' ? $finalAmount : null,
                'remaining_balance' => $paymentType === 'partial' ? ($remaining - $finalAmount) : null,
                'currency' => $loan->currency,
                'status' => 'pending',
                'payment_type' => $paymentType,
            ]);

            $gatewayResult = $gateway->createPaymentLink($finalAmount, $loan->currency, [
                'payment_id' => (string) $payment->id,
                'loan_id' => (string) $loan->id,
                'success_url' => url('/api/payments/success'),
                'cancel_url' => url('/api/payments/cancel'),
            ]);

            $paymentLink = is_string($gatewayResult) ? $gatewayResult : ($gatewayResult['url'] ?? '');
            $gatewayPaymentId = is_array($gatewayResult) ? ($gatewayResult['id'] ?? null) : null;

            $payment->update([
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_response' => ['payment_link' => $paymentLink],
            ]);

            $this->activityLogService->log(
                description: 'Payment initiated #' . $payment->id . ' for loan #' . $loan->id,
                event: 'payment_initiated',
                subjectType: Payment::class,
                subjectId: $payment->id,
                properties: [
                    'loan_id' => $loan->id,
                    'loan_number' => $loan->loan_number,
                    'amount' => $finalAmount,
                    'payment_type' => $paymentType,
                    'transaction_id' => $payment->transaction_id,
                    'gateway' => 'stripe',
                ],
                logName: 'payment'
            );

            return [
                'payment' => PaymentResource::make($payment),
                'payment_link' => $paymentLink,
            ];
        });
    }

    public function initiateFromSignedLink(EmiSchedule $emiSchedule): array
    {
        $loan = $emiSchedule->loan;

        if ($loan->status !== 'active') {
            throw new HttpException(422, 'Loan is not active.');
        }

        if ($emiSchedule->status !== 'pending') {
            throw new HttpException(422, 'EMI is not pending.');
        }

        $this->tenantService->setCurrentTenant($loan->tenant);

        $gateway = $this->getGateway();

        return DB::transaction(function () use ($loan, $emiSchedule, $gateway): array {
            $payment = Payment::create([
                'tenant_id' => $loan->tenant_id,
                'loan_id' => $loan->id,
                'emi_schedule_id' => $emiSchedule->id,
                'borrower_id' => $loan->borrower_id,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                'gateway' => 'stripe',
                'amount' => $emiSchedule->total_amount,
                'currency' => $loan->currency,
                'status' => 'pending',
                'payment_type' => 'full',
            ]);

            $gatewayResult = $gateway->createPaymentLink(
                (float) $emiSchedule->total_amount,
                $loan->currency,
                [
                    'payment_id' => (string) $payment->id,
                    'loan_id' => (string) $loan->id,
                    'success_url' => url('/api/payments/success'),
                    'cancel_url' => url('/api/payments/cancel'),
                ]
            );

            $paymentLink = is_string($gatewayResult) ? $gatewayResult : ($gatewayResult['url'] ?? '');
            $gatewayPaymentId = is_array($gatewayResult) ? ($gatewayResult['id'] ?? null) : null;

            $payment->update([
                'gateway_payment_id' => $gatewayPaymentId,
                'gateway_response' => ['payment_link' => $paymentLink],
            ]);

            return [
                'payment_id' => $payment->id,
                'payment_link' => $paymentLink,
            ];
        });
    }

    public function handleWebhook(Request $request): void
    {
        $result = $this->getGateway()->processWebhook($request);

        PaymentGatewayLog::create([
            'gateway' => 'stripe',
            'event_type' => $result['event'] ?? 'unknown',
            'payload' => ['result' => $result, 'headers' => $request->headers->all()],
            'processed' => $result['status'] !== 'failed',
        ]);

        if (isset($result['error'])) {
            return;
        }

        if ($result['gateway_payment_id']) {
            $payment = Payment::where('gateway_payment_id', $result['gateway_payment_id'])->first();

            if ($payment) {
                $payment->update([
                    'status' => $result['status'],
                    'gateway_response' => array_merge($payment->gateway_response ?? [], ['webhook_event' => $result['event']]),
                    'paid_at' => $result['status'] === 'completed' ? now() : $payment->paid_at,
                ]);

                if ($result['status'] === 'completed') {
                    if ($payment->emi_schedule_id) {
                        $emiSchedule = EmiSchedule::find($payment->emi_schedule_id);
                        if ($emiSchedule) {
                            $newPaid = ($emiSchedule->paid_amount ?? 0) + ($payment->paid_amount ?? $payment->amount);
                            $remaining = max(0, $emiSchedule->total_amount - $newPaid);

                            $update = [
                                'paid_amount' => $newPaid,
                            ];

                            if ($remaining <= 0) {
                                $update['status'] = 'paid';
                                $update['paid_at'] = now();
                            }

                            $emiSchedule->update($update);
                        }
                    }

                    Event::dispatch(new PaymentReceived($payment));
                }
            }
        }
    }

    protected function getGateway(): PaymentGatewayInterface
    {
        if ($this->gateway instanceof PaymentGatewayInterface) {
            return $this->gateway;
        }

        $tenant = $this->tenantService->getCurrentTenant();

        /** @var array<string, mixed>|null $rawConfig */
        $rawConfig = $tenant instanceof Tenant ? $tenant->payment_gateway_config : null;
        /** @var array<string, mixed> $config */
        $config = is_array($rawConfig) ? $rawConfig : [];

        $gatewayType = is_string($config['gateway'] ?? null) ? $config['gateway'] : 'stripe';

        $this->gateway = match ($gatewayType) {
            default => new StripeGateway($config),
        };

        return $this->gateway;
    }
}
