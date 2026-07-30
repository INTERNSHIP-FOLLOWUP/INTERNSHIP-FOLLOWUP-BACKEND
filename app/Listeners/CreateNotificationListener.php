<?php

namespace App\Listeners;

use App\Events\NotificationEvent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(NotificationEvent $event): void
    {
        $service = app(NotificationService::class);

        $service->createForUser($event->user, [
            'sender_id' => $event->senderId,
            'sender_type' => $event->senderType,
            'event' => $event->type,
            'category' => $event->category,
            'priority' => $event->priority,
            'title' => $event->title,
            'message' => $event->message,
            'action_url' => $event->actionUrl,
            'reference_type' => $event->referenceType,
            'reference_id' => $event->referenceId,
            'metadata' => $event->metadata,
        ]);
    }
}