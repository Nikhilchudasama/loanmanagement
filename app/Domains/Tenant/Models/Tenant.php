<?php

declare(strict_types=1);

namespace App\Domains\Tenant\Models;

use App\Domains\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'company_name',
        'logo',
        'timezone',
        'default_currency',
        'payment_gateway_config',
        'email_config',
        'notification_settings',
        'status',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'payment_gateway_config' => 'array',
            'email_config' => 'array',
            'notification_settings' => 'array',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Domains\Auth\Models\User, $this> */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
