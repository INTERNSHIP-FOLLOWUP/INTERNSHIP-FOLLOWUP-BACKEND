<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificationPolicy
{
    /**
     * Determine whether the user can view any notifications.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the notification.
     */
    public function view(User $user, Notification $notification): Response|bool
    {
        return $notification->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can create notifications.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the notification.
     */
    public function update(User $user, Notification $notification): Response|bool
    {
        return $notification->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can delete the notification.
     */
    public function delete(User $user, Notification $notification): Response|bool
    {
        return $notification->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can restore the notification.
     */
    public function restore(User $user, Notification $notification): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the notification.
     */
    public function forceDelete(User $user, Notification $notification): bool
    {
        return false;
    }
}