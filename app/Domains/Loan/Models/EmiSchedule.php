<?php

declare(strict_types=1);

namespace App\Domains\Loan\Models;

use App\Support\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class EmiSchedule extends Model
{
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'loan_id',
        'emi_number',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'paid_amount',
        'outstanding_balance',
        'status',
        'paid_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Loan\Models\Loan, $this> */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
