<?php

namespace App\Notifications;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IssueAssigned extends Notification
{
    use Queueable;

    public function __construct(private Issue $issue)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'issue_assigned',
            'issue_id' => $this->issue->id,
            'title' => $this->issue->title,
            'priority' => $this->issue->priority,
            'message' => sprintf('A new issue was raised for you: "%s".', $this->issue->title),
        ];
    }
}
