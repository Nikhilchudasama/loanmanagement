<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'gateway',
        'event_type',
        'payload',
        'processed',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
