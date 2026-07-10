<?php

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Resources\NotificationResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notifications
 *
 * APIs for notifications
 */

class NotificationController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->per_page ?: 15;
        $user = auth()->user();

        $notifications = $user->notifications()->paginate($perPage);

        return $this->success(
            NotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->unreadNotifications()->count();

        return $this->success(['count' => $count]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(message: 'Notification marked as read.');
    }

    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(message: 'All notifications marked as read.');
    }
}
