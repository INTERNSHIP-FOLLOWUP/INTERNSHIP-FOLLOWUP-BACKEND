<?php

namespace App\Traits;

use App\Services\NotificationService;
use App\Models\User;

trait SendsNotifications
{
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = app(NotificationService::class);
    }

    /**
     * Send a notification to a user.
     */
    protected function notify(
        int|User $receiver,
        string $title,
        string $message,
        string $type = 'general',
        string $priority = 'normal',
        ?int $senderId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $actionUrl = null
    ): void {
        $receiverId = $receiver instanceof User ? $receiver->id : $receiver;

        $this->notificationService->sendNotification(
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
    }

    /**
     * Send notifications to multiple users.
     */
    protected function notifyMany(
        array $receiverIds,
        string $title,
        string $message,
        string $type = 'general',
        string $priority = 'normal',
        ?int $senderId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $actionUrl = null
    ): void {
        foreach ($receiverIds as $receiverId) {
            $this->notificationService->sendNotification(
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
        }
    }
}