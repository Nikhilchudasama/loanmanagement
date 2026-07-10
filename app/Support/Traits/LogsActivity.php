<?php

declare(strict_types=1);

namespace App\Support\Traits;

use App\Domains\ActivityLog\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        $events = property_exists(static::class, 'recordActivityEvents')
            ? static::$recordActivityEvents
            : ['created', 'updated', 'deleted', 'restored'];

        if (in_array('created', $events, true)) {
            static::created(function (Model $model): void {
                static::logEvent($model, 'created');
            });
        }

        if (in_array('updated', $events, true)) {
            static::updated(function (Model $model): void {
                $dirty = $model->getDirty();
                unset($dirty['updated_at']);

                if ($dirty !== []) {
                    static::logEvent($model, 'updated');
                }
            });
        }

        if (in_array('deleted', $events, true)) {
            static::deleted(function (Model $model): void {
                static::logEvent($model, 'deleted');
            });
        }

        if (in_array('restored', $events, true) && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            /** @phpstan-ignore-next-line SoftDeletes models have restored() at runtime */
            static::restored(function (Model $model): void {
                static::logEvent($model, 'restored');
            });
        }
    }

    protected static function logEvent(Model $model, string $event): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();
        $original = $model->getOriginal();
        $changes = $model->getChanges();

        /** @var array<string, mixed> $properties */
        $properties = [
            'old' => match ($event) {
                'updated' => array_intersect_key($original, $changes),
                'deleted' => $original,
                default => null,
            },
            'new' => match ($event) {
                'created' => $model->toArray(),
                'updated' => $changes,
                default => null,
            },
        ];

        /** @var string $logName */
        $logName = property_exists($model, 'logName') ? $model->logName : 'default';

        $ip = app()->runningInConsole() ? null : request()->ip();
        $userAgent = app()->runningInConsole() ? null : request()->userAgent();

        ActivityLog::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'log_name' => $logName,
            'description' => class_basename($model) . ' ' . $event . ($model->getKey() ? ' #' . $model->getKey() : ''),
            'event' => $event,
            'subject_type' => $model::class,
            'subject_id' => $model->getKey(),
            'properties' => $properties,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
