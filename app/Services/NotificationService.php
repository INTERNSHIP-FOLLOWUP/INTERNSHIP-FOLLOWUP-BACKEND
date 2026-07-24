<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    public function createNotification(
        int $receiverId,
        string $title,
        string $message,
        string $type = 'general',
        string $priority = 'normal',
        ?int $senderId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $actionUrl = null
    ): Notification {
        return Notification::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);
    }

    public function sendNotification(
        int $receiverId,
        string $title,
        string $message,
        string $type = 'general',
        string $priority = 'normal',
        ?int $senderId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $actionUrl = null
    ): Notification {
        $notification = $this->createNotification(
            $receiverId,
            $title,
            $message,
            $type,
            $priority,
            $senderId,
            $entityType,
            $entityId,
            $actionUrl
        );

        // TODO: Future broadcasting support - dispatch notification event
        // event(new NotificationCreated($notification));

        return $notification;
    }

    public function getNotificationsForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Notification::forUser($user->id)
            ->with(['sender', 'sender.role', 'sender.company', 'receiver', 'receiver.role', 'receiver.company'])
            ->newestFirst()
            ->paginate($perPage);
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->count();
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::forUser($user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }

    public function filterNotifications(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Notification::forUser($user->id)
            ->with(['sender', 'sender.role', 'sender.company', 'receiver', 'receiver.role', 'receiver.company'])
            ->newestFirst();

        if (isset($filters['type']) && $filters['type']) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['priority']) && $filters['priority']) {
            $query->ofPriority($filters['priority']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->unread();
            } elseif ($filters['status'] === 'read') {
                $query->read();
            }
        }

        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['sort'])) {
            if ($filters['sort'] === 'oldest') {
                $query->oldestFirst();
            }
        }

        return $query->paginate($perPage);
    }
}