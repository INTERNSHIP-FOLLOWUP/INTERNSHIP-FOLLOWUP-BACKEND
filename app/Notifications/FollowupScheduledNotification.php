<?php

namespace App\Notifications;

use App\Models\Followup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowupScheduledNotification extends Notification
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
        // For now, we'll use database channel
        // Can be extended to include mail, SMS, etc.
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
            'title' => 'Follow-up Meeting Scheduled',
            'message' => "A {$meetingType} follow-up meeting has been scheduled for {$studentName} on {$meetingDate}.",
            'followup_id' => $this->followup->id,
            'student_id' => $this->followup->student_id,
            'student_name' => $studentName,
            'meeting_type' => $meetingType,
            'meeting_date' => $meetingDate,
            'type' => 'followup_scheduled',
        ];
    }
}