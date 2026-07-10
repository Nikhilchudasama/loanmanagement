<?php

declare(strict_types=1);

namespace App\Domains\Loan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmiScheduleResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'loan_id' => $this->resource->loan_id,
            'emi_number' => $this->resource->emi_number,
            'due_date' => $this->resource->due_date?->format('Y-m-d'),
            'principal_amount' => (float) $this->resource->principal_amount,
            'interest_amount' => (float) $this->resource->interest_amount,
            'total_amount' => (float) $this->resource->total_amount,
            'outstanding_balance' => (float) $this->resource->outstanding_balance,
            'status' => $this->resource->status,
            'paid_at' => $this->resource->paid_at?->toISOString(),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
