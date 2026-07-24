<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkNotificationReadRequest;
use App\Http\Requests\DeleteNotificationRequest;
use App\Http\Requests\FilterNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(): JsonResponse
    {
        $notifications = $this->notificationService->getNotificationsForUser(request()->user());

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function show(Notification $notification): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount(request()->user());

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($notification);

        return response()->json([
            'success' => true,
            'data' => new NotificationResource($notification),
        ]);
    }

    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(request()->user());

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data' => ['updated_count' => $count],
        ]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->notificationService->deleteNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }

    public function filter(FilterNotificationRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $notifications = $this->notificationService->filterNotifications(request()->user(), $filters);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }
}