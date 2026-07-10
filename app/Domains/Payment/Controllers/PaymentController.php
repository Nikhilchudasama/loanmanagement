<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Requests\InitiatePaymentRequest;
use App\Domains\Payment\Resources\PaymentResource;
use App\Domains\Payment\Services\PaymentService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Payment Management
 *
 * APIs for managing payments
 */

class PaymentController
{
    use ApiResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            $this->paymentService->list((int) $request->per_page)
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->success(
            PaymentResource::make($this->paymentService->find($payment))
        );
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $loan = Loan::findOrFail($request->loan_id);

        $result = $this->paymentService->initiatePayment(
            $loan,
            $request->emi_schedule_id,
            $request->payment_type ?? 'full',
            $request->amount ? (float) $request->amount : null,
        );

        return $this->success($result, 'Payment initiated successfully.');
    }

    /**
     * @unauthenticated
     */
    public function securePay(EmiSchedule $emiSchedule): JsonResponse
    {
        $result = $this->paymentService->initiateFromSignedLink($emiSchedule);

        return $this->success($result, 'Payment initiated successfully.');
    }

    /**
     * @unauthenticated
     */
    public function webhook(Request $request): JsonResponse
    {
        $this->paymentService->handleWebhook($request);

        return response()->json(['status' => 'ok']);
    }
}
