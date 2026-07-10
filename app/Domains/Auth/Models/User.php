<?php

declare(strict_types=1);

namespace App\Domains\Auth\Models;

use App\Domains\Tenant\Models\Tenant;
use App\Support\Traits\LogsActivity;
use App\Support\Traits\TenantScoped;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read Tenant|null $tenant
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'mobile_number',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isLoanOfficer(): bool
    {
        return $this->hasRole('loan-officer');
    }

    public function isBorrower(): bool
    {
        return $this->hasRole('borrower');
    }
}
