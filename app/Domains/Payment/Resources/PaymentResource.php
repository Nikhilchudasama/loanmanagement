<?php

declare(strict_types=1);

namespace App\Domains\Payment\Resources;

use App\Domains\Borrower\Resources\BorrowerResource;
use App\Domains\Loan\Resources\EmiScheduleResource;
use App\Domains\Loan\Resources\LoanResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'loan_id' => $this->resource->loan_id,
            'emi_schedule_id' => $this->resource->emi_schedule_id,
            'borrower_id' => $this->resource->borrower_id,
            'transaction_id' => $this->resource->transaction_id,
            'gateway' => $this->resource->gateway,
            'gateway_payment_id' => $this->resource->gateway_payment_id,
            'amount' => (float) $this->resource->amount,
            'currency' => $this->resource->currency,
            'status' => $this->resource->status,
            'payment_type' => $this->resource->payment_type,
            'failure_reason' => $this->resource->failure_reason,
            'retry_count' => $this->resource->retry_count,
            'paid_at' => $this->resource->paid_at?->toISOString(),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'loan' => LoanResource::make($this->whenLoaded('loan')),
            'borrower' => BorrowerResource::make($this->whenLoaded('borrower')),
            'emi_schedule' => EmiScheduleResource::make($this->whenLoaded('emiSchedule')),
        ];
    }
}
