<?php

namespace App\Notifications;

use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EvaluationSubmitted extends Notification
{
    use Queueable;

    public function __construct(private Evaluation $evaluation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $company = $this->evaluation->supervisor?->company;

        return [
            'type' => 'evaluation_submitted',
            'evaluation_id' => $this->evaluation->id,
            'student_id' => $this->evaluation->student_id,
            'student_name' => $this->evaluation->student?->name,
            'company_name' => $company?->company_name,
            'overall_score' => $this->evaluation->overall_score,
            'message' => sprintf(
                '%s submitted an evaluation for %s.',
                $company?->company_name ?? 'A company',
                $this->evaluation->student?->name ?? 'a student',
            ),
        ];
    }
}
