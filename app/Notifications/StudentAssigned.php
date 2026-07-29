<?php

namespace App\Notifications;

use App\Models\InternshipAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentAssigned extends Notification
{
    use Queueable;

    public function __construct(private InternshipAssignment $assignment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'student_assigned',
            'student_id' => $this->assignment->student_id,
            'student_name' => $this->assignment->student?->name,
            'position' => $this->assignment->position,
            'message' => sprintf(
                '%s has been assigned to your company as %s.',
                $this->assignment->student?->name ?? 'A new student',
                $this->assignment->position ?? 'an intern',
            ),
        ];
    }
}
