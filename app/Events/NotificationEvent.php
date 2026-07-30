<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent
{
    use Dispatchable, SerializesModels;

    public User $user;
    public string $type;
    public string $category;
    public string $priority;
    public string $title;
    public string $message;
    public ?string $actionUrl;
    public ?string $referenceType;
    public ?int $referenceId;
    public ?array $metadata;
    public ?string $senderType;
    public ?int $senderId;

    /**
     * Create a new event instance.
     */
    public function __construct(
        User $user,
        string $type,
        string $category,
        string $priority,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null,
        ?string $senderType = null,
        ?int $senderId = null
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->category = $category;
        $this->priority = $priority;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->referenceType = $referenceType;
        $this->referenceId = $referenceId;
        $this->metadata = $metadata;
        $this->senderType = $senderType;
        $this->senderId = $senderId;
    }
}
