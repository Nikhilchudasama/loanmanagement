<?php

declare(strict_types=1);

namespace App\Domains\Borrower\Models;

use App\Domains\Tenant\Models\Tenant;
use App\Support\Traits\LogsActivity;
use App\Support\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \App\Domains\Tenant\Models\Tenant|null $tenant
 */
class Borrower extends Model
{
    use TenantScoped, SoftDeletes, LogsActivity;
    protected $fillable = [
        'tenant_id',
        'full_name',
        'email',
        'mobile_number',
        'date_of_birth',
        'address',
        'national_id',
        'status',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Domains\Tenant\Models\Tenant, $this> */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
