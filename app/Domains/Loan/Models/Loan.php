<?php

declare(strict_types=1);

namespace App\Domains\Loan\Models;

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Auth\Models\User;
use App\Support\Traits\LogsActivity;
use App\Support\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \App\Domains\Tenant\Models\Tenant|null $tenant
 * @property-read \App\Domains\Borrower\Models\Borrower|null $borrower
 * @property-read \App\Domains\Auth\Models\User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Loan\Models\EmiSchedule> $emiSchedules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Domains\Payment\Models\Payment> $payments
 */
class Loan extends Model
{
    use TenantScoped, SoftDeletes, LogsActivity;
    protected $fillable = [
        'tenant_id',
        'borrower_id',
        'loan_number',
        'loan_type',
        'loan_amount',
        'interest_rate',
        'loan_tenure_months',
        'emi_start_date',
        'processing_fee',
        'foreclosure_charges',
        'currency',
        'interest_type',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'foreclosed_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'loan_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'emi_start_date' => 'date',
            'approved_at' => 'datetime',
            'foreclosed_at' => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Tenant\Models\Tenant, $this> */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Borrower\Models\Borrower, $this> */
    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Auth\Models\User, $this> */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Domains\Loan\Models\EmiSchedule, $this> */
    public function emiSchedules()
    {
        return $this->hasMany(EmiSchedule::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Domains\Payment\Models\Payment, $this> */
    public function payments()
    {
        return $this->hasMany(\App\Domains\Payment\Models\Payment::class);
    }
}
