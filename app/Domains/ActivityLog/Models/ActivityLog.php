<?php

namespace App\Domains\ActivityLog\Models;

use App\Domains\Tenant\Models\Tenant;
use App\Domains\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Tenant|null $tenant
 * @property-read User|null $user
 * @property-read Model|null $subject
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'log_name',
        'description',
        'event',
        'subject_type',
        'subject_id',
        'properties',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
