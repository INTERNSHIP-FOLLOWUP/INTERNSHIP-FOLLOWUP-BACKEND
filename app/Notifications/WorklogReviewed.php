<?php

namespace App\Notifications;

use App\Models\Worklog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorklogReviewed extends Notification
{
    use Queueable;

    public function __construct(private Worklog $worklog)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'worklog_reviewed',
            'worklog_id' => $this->worklog->id,
            'status' => $this->worklog->status,
            'week_number' => $this->worklog->week_number,
            'message' => sprintf(
                'Your Week %s worklog was marked "%s".',
                $this->worklog->week_number,
                $this->worklog->status,
            ),
        ];
    }
}
