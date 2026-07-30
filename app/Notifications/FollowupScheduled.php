<?php

namespace App\Notifications;

use App\Models\Followup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowupScheduled extends Notification
{
    use Queueable;

    public function __construct(private Followup $followup)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $meetingDate = $this->followup->meeting_date
            ? \Illuminate\Support\Carbon::parse($this->followup->meeting_date)->format('M j, Y')
            : null;

        return [
            'type' => 'followup_scheduled',
            'followup_id' => $this->followup->id,
            'meeting_type' => $this->followup->meeting_type,
            'meeting_date' => $meetingDate,
            'message' => sprintf(
                'Your tutor recorded a %s follow-up%s.',
                strtolower((string) $this->followup->meeting_type),
                $meetingDate ? " for {$meetingDate}" : '',
            ),
        ];
    }
}
