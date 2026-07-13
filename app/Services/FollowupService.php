<?php

namespace App\Services;

use App\Models\Followup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class FollowupService
{
    /**
     * Create a new follow-up and send notifications.
     *
     * @param array $data
     * @return Followup
     */
    public function createFollowup(array $data): Followup
    {
        $followup = Followup::create($data);
        
        // Send notifications to student and tutor
        $this->sendFollowupNotifications($followup);
        
        return $followup;
    }

    /**
     * Update follow-up and send notifications if needed.
     *
     * @param Followup $followup
     * @param array $data
     * @return Followup
     */
    public function updateFollowup(Followup $followup, array $data): Followup
    {
        $followup->update($data);
        
        // Send notification about follow-up update
        $this->sendFollowupUpdateNotification($followup);
        
        return $followup;
    }

    /**
     * Send notifications when a new follow-up is created.
     *
     * @param Followup $followup
     * @return void
     */
    private function sendFollowupNotifications(Followup $followup): void
    {
        $student = $followup->student;
        $tutor = $followup->tutor;
        $company = $followup->company;

        // Notify student
        if ($student && $student->user) {
            Notification::send($student->user, new \App\Notifications\FollowupScheduledNotification($followup));
        }

        // Notify tutor
        if ($tutor) {
            Notification::send($tutor, new \App\Notifications\FollowupScheduledNotification($followup));
        }

        // Notify company representative if assigned
        if ($company) {
            // Assuming company has a user relationship (company representative)
            // This will need to be adjusted based on your actual company-user relationship
            // For now, we'll skip company notification
        }
    }

    /**
     * Send notification when follow-up is updated.
     *
     * @param Followup $followup
     * @return void
     */
    private function sendFollowupUpdateNotification(Followup $followup): void
    {
        $student = $followup->student;
        $tutor = $followup->tutor;

        // Notify student about update
        if ($student && $student->user) {
            Notification::send($student->user, new \App\Notifications\FollowupUpdatedNotification($followup));
        }

        // Notify tutor about update
        if ($tutor) {
            Notification::send($tutor, new \App\Notifications\FollowupUpdatedNotification($followup));
        }
    }

    /**
     * Get upcoming follow-ups for a tutor.
     *
     * @param User $tutor
     * @param int $daysAhead
     * @return array
     */
    public function getUpcomingFollowups(User $tutor, int $daysAhead = 7): array
    {
        $followups = Followup::where('tutor_id', $tutor->id)
            ->where('meeting_date', '>=', now()->toDateString())
            ->where('meeting_date', '<=', now()->addDays($daysAhead)->toDateString())
            ->with(['student', 'company'])
            ->orderBy('meeting_date', 'asc')
            ->get();

        return $followups->toArray();
    }

    /**
     * Get overdue follow-ups (next_followup date has passed).
     *
     * @param User $tutor
     * @return array
     */
    public function getOverdueFollowups(User $tutor): array
    {
        $followups = Followup::where('tutor_id', $tutor->id)
            ->where('next_followup', '<', now()->toDateString())
            ->whereNotNull('next_followup')
            ->with(['student', 'company'])
            ->orderBy('next_followup', 'asc')
            ->get();

        return $followups->toArray();
    }

    /**
     * Calculate follow-up statistics for a tutor.
     *
     * @param User $tutor
     * @return array
     */
    public function getFollowupStatistics(User $tutor): array
    {
        $total = Followup::where('tutor_id', $tutor->id)->count();
        
        $byType = Followup::where('tutor_id', $tutor->id)
            ->select('meeting_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('meeting_type')
            ->get()
            ->pluck('count', 'meeting_type');

        $upcoming = Followup::where('tutor_id', $tutor->id)
            ->where('meeting_date', '>=', now()->toDateString())
            ->where('meeting_date', '<=', now()->addDays(30)->toDateString())
            ->count();

        $overdue = Followup::where('tutor_id', $tutor->id)
            ->where('next_followup', '<', now()->toDateString())
            ->whereNotNull('next_followup')
            ->count();

        return [
            'total' => $total,
            'by_type' => $byType,
            'upcoming' => $upcoming,
            'overdue' => $overdue,
        ];
    }
}