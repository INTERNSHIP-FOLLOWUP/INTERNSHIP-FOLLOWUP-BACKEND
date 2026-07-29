<?php

namespace App\Notifications;

use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IssueReportedToAdmin extends Notification
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
            'type' => 'issue_reported',
            'issue_id' => $this->issue->id,
            'student_id' => $this->issue->student_id,
            'title' => $this->issue->title,
            'priority' => $this->issue->priority,
            'message' => sprintf(
                'New issue reported for %s: "%s".',
                $this->issue->student?->name ?? 'a student',
                $this->issue->title,
            ),
        ];
    }
}
