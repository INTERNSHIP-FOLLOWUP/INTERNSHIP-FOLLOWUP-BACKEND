<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class NotificationService
{
    public function getForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $user->id)
            ->with(['sender', 'reference']);

        $sort = Arr::get($filters, 'sort', 'desc');
        if (in_array($sort, ['asc', 'desc'])) {
            $query->orderBy('created_at', $sort);
        } else {
            $query->orderByDesc('created_at');
        }

        if ($category = Arr::get($filters, 'category')) {
            $query->where('category', $category);
        }

        if ($event = Arr::get($filters, 'event')) {
            $query->where('event', $event);
        }

        if ($priority = Arr::get($filters, 'priority')) {
            $query->where('priority', $priority);
        }

        $isRead = Arr::get($filters, 'is_read');
        if ($isRead !== null) {
            $query->where('is_read', filter_var($isRead, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($referenceType = Arr::get($filters, 'reference_type')) {
            $query->where('reference_type', $referenceType);
        }

        if ($referenceId = Arr::get($filters, 'reference_id')) {
            $query->where('reference_id', $referenceId);
        }

        if ($dateFrom = Arr::get($filters, 'date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = Arr::get($filters, 'date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $perPage = (int) Arr::get($filters, 'per_page', 15);

        return $query->paginate(min($perPage, 100));
    }

    public function getLatestForUser(User $user, int $limit = 10): array
    {
        return Notification::where('user_id', $user->id)
            ->with(['sender', 'reference'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUnreadCountForUser(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notification->fresh();
    }

    public function markAsUnread(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): void
    {
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function deleteRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', true)
            ->delete();
    }

    public function deleteAll(User $user): int
    {
        return Notification::where('user_id', $user->id)->delete();
    }

    public function markMultipleAsRead(User $user, array $ids): void
    {
        Notification::whereIn('id', $ids)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function markMultipleAsUnread(User $user, array $ids): void
    {
        Notification::whereIn('id', $ids)
            ->where('user_id', $user->id)
            ->where('is_read', true)
            ->update([
                'is_read' => false,
                'read_at' => null,
            ]);
    }

    public function deleteMultiple(User $user, array $ids): int
    {
        return Notification::whereIn('id', $ids)
            ->where('user_id', $user->id)
            ->delete();
    }

    public function createForUser(User $user, array $data): Notification
    {
        return Notification::create(array_merge($data, [
            'user_id' => $user->id,
        ]));
    }

    public function sendToUser(User $user, array $data): Notification
    {
        return $this->createForUser($user, $data);
    }

    public function sendToUsers(array $users, array $data): void
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $data);
        }
    }

    public function sendToRole(string $role, array $data): int
    {
        $users = User::whereHas('role', function ($q) use ($role) {
            $q->where('name', $role);
        })->get();

        $this->sendToUsers($users->all(), $data);

        return $users->count();
    }

    public function sendAnnouncement(array $data): int
    {
        $users = User::all();

        $this->sendToUsers($users->all(), $data);

        return $users->count();
    }

    public function createGeneralNotification(User $user, array $data): Notification
    {
        return $this->sendToUser($user, array_merge($data, [
            'category' => 'general',
        ]));
    }

    public function createEvaluationNotification(User $user, array $data): Notification
    {
        return $this->sendToUser($user, array_merge($data, [
            'category' => 'evaluation',
        ]));
    }
}