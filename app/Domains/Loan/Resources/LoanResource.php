<?php

declare(strict_types=1);

namespace App\Domains\Loan\Resources;

use App\Domains\Borrower\Resources\BorrowerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'borrower_id' => $this->resource->borrower_id,
            'loan_number' => $this->resource->loan_number,
            'loan_type' => $this->resource->loan_type,
            'loan_amount' => (float) $this->resource->loan_amount,
            'interest_rate' => (float) $this->resource->interest_rate,
            'loan_tenure_months' => $this->resource->loan_tenure_months,
            'emi_start_date' => $this->resource->emi_start_date?->format('Y-m-d'),
            'processing_fee' => (float) $this->resource->processing_fee,
            'currency' => $this->resource->currency,
            'interest_type' => $this->resource->interest_type,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'approved_by' => $this->resource->approved_by,
            'approved_at' => $this->resource->approved_at?->toISOString(),
            'foreclosure_charges' => (float) $this->resource->foreclosure_charges,
            'foreclosed_at' => $this->resource->foreclosed_at?->toISOString(),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'borrower' => BorrowerResource::make($this->whenLoaded('borrower')),
            'emi_schedules' => EmiScheduleResource::collection($this->whenLoaded('emiSchedules')),
        ];
    }
}
