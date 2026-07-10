<?php

namespace App\Domains\ActivityLog\Services;

use App\Domains\ActivityLog\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function list(int $perPage = 15): array
    {
        $user = auth()->user();
        $query = ActivityLog::with('user')->latest();

        if ($user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->paginate($perPage)->toArray();
    }

    public function log(
        string $description,
        ?string $event = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $properties = null,
        ?string $logName = null,
    ): ActivityLog {
        $user = Auth::user();

        $ip = app()->runningInConsole() ? 'console' : request()->ip();
        $userAgent = app()->runningInConsole() ? 'console' : request()->userAgent();

        return ActivityLog::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'log_name' => $logName ?? 'default',
            'description' => $description,
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
