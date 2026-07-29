<?php

namespace App\Notifications;

use App\Models\Worklog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorklogSubmitted extends Notification
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
            'type' => 'worklog_submitted',
            'worklog_id' => $this->worklog->id,
            'student_id' => $this->worklog->student_id,
            'student_name' => $this->worklog->student?->name,
            'week_number' => $this->worklog->week_number,
            'message' => sprintf(
                '%s submitted a Week %s worklog.',
                $this->worklog->student?->name ?? 'A student',
                $this->worklog->week_number,
            ),
        ];
    }
}
