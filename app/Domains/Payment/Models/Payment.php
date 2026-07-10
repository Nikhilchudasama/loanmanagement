<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Loan\Models\EmiSchedule;
use App\Domains\Loan\Models\Loan;
use App\Domains\Tenant\Models\Tenant;
use App\Support\Traits\LogsActivity;
use App\Support\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Domains\Tenant\Models\Tenant|null $tenant
 * @property-read \App\Domains\Loan\Models\Loan|null $loan
 * @property-read \App\Domains\Loan\Models\EmiSchedule|null $emiSchedule
 * @property-read \App\Domains\Borrower\Models\Borrower|null $borrower
 */
class Payment extends Model
{
    use TenantScoped, LogsActivity;
    protected $fillable = [
        'tenant_id',
        'loan_id',
        'emi_schedule_id',
        'borrower_id',
        'transaction_id',
        'gateway',
        'gateway_payment_id',
        'amount',
        'paid_amount',
        'remaining_balance',
        'currency',
        'status',
        'payment_type',
        'gateway_response',
        'failure_reason',
        'retry_count',
        'paid_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Tenant\Models\Tenant, $this> */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Loan\Models\Loan, $this> */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Loan\Models\EmiSchedule, $this> */
    public function emiSchedule()
    {
        return $this->belongsTo(EmiSchedule::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Borrower\Models\Borrower, $this> */
    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }
}
