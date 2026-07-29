<?php

namespace App\Notifications;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IssueResolved extends Notification
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
            'type' => 'issue_resolved',
            'issue_id' => $this->issue->id,
            'title' => $this->issue->title,
            'message' => sprintf('Your issue "%s" has been resolved.', $this->issue->title),
        ];
    }
}
