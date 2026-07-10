<?php

declare(strict_types=1);

namespace App\Domains\Borrower\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowerResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'full_name' => $this->resource->full_name,
            'email' => $this->resource->email,
            'mobile_number' => $this->resource->mobile_number,
            'date_of_birth' => $this->resource->date_of_birth?->format('Y-m-d'),
            'address' => $this->resource->address,
            'national_id' => $this->resource->national_id,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
