<?php

declare(strict_types=1);

namespace App\Domains\Notification\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => class_basename($this->resource->type),
            'data' => $this->resource->data,
            'read_at' => $this->resource->read_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
