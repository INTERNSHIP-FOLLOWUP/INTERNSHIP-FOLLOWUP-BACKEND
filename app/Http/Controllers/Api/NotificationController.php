<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $filters = $request->only([
            'category', 'event', 'priority', 'is_read', 'search',
            'reference_type', 'reference_id', 'per_page', 'sort',
            'date_from', 'date_to'
        ]);

        $notifications = $this->notificationService->getForUser($user, $filters);

        return response()->json([
            'data' => $notifications->items(),
            'message' => 'Notifications retrieved successfully.',
            'meta' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, Notification $notification): JsonResponse
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $notification->load(['sender', 'reference']);

        if (!$notification->is_read) {
            $this->notificationService->markAsRead($notification);
            $notification->refresh();
        }

        return response()->json([
            'data' => $notification,
            'message' => 'Notification retrieved successfully.',
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = (int) $request->query('limit', 10);

        $notifications = $this->notificationService->getLatestForUser($user, $limit);

        return response()->json([
            'data' => $notifications,
            'message' => 'Latest notifications retrieved successfully.',
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = Auth::user();
        $count = $this->notificationService->getUnreadCountForUser($user);

        return response()->json([
            'data' => ['count' => $count],
            'message' => 'Unread count retrieved successfully.',
        ]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $notification = $this->notificationService->markAsRead($notification);

        return response()->json([
            'data' => $notification,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAsUnread(Request $request, Notification $notification): JsonResponse
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $notification = $this->notificationService->markAsUnread($notification);

        return response()->json([
            'data' => $notification,
            'message' => 'Notification marked as unread.',
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = Auth::user();

        $this->notificationService->markAllAsRead($user);

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $user = Auth::user();

        if ($notification->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully.',
        ]);
    }

    public function deleteRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $deleted = $this->notificationService->deleteRead($user);

        return response()->json([
            'data' => ['deleted' => $deleted],
            'message' => 'Read notifications deleted successfully.',
        ]);
    }

    public function deleteAll(Request $request): JsonResponse
    {
        $user = Auth::user();
        $deleted = $this->notificationService->deleteAll($user);

        return response()->json([
            'data' => ['deleted' => $deleted],
            'message' => 'All notifications deleted successfully.',
        ]);
    }

    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Invalid IDs provided.'], 422);
        }

        $this->notificationService->markMultipleAsRead($user, $ids);

        return response()->json([
            'message' => 'Notifications marked as read.',
        ]);
    }

    public function bulkMarkAsUnread(Request $request): JsonResponse
    {
        $user = Auth::user();
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Invalid IDs provided.'], 422);
        }

        $this->notificationService->markMultipleAsUnread($user, $ids);

        return response()->json([
            'message' => 'Notifications marked as unread.',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $user = Auth::user();
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Invalid IDs provided.'], 422);
        }

        $deleted = $this->notificationService->deleteMultiple($user, $ids);

        return response()->json([
            'data' => ['deleted' => $deleted],
            'message' => 'Notifications deleted successfully.',
        ]);
    }
}