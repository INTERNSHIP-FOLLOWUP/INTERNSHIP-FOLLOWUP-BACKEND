<?php

namespace App\Notifications;

use App\Models\Followup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowupUpdatedNotification extends Notification
{
    use Queueable;

    public Followup $followup;

    /**
     * Create a new notification instance.
     */
    public function __construct(Followup $followup)
    {
        $this->followup = $followup;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation for database channel.
     */
    public function toArray(object $notifiable): array
    {
        $studentName = $this->followup->student->name ?? 'Unknown Student';
        $meetingType = $this->followup->meeting_type;
        $meetingDate = $this->followup->meeting_date->format('M d, Y');

        return [
            'title' => 'Follow-up Meeting Updated',
            'message' => "A {$meetingType} follow-up meeting for {$studentName} on {$meetingDate} has been updated.",
            'followup_id' => $this->followup->id,
            'student_id' => $this->followup->student_id,
            'student_name' => $studentName,
            'meeting_type' => $meetingType,
            'meeting_date' => $meetingDate,
            'type' => 'followup_updated',
        ];
    }
}