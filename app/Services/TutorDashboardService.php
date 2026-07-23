<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Worklog;
use App\Models\Issue;
use App\Models\Followup;
use App\Models\InternshipAssignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TutorDashboardService
{
    public function getTutorDashboard(int $tutorId): array
    {
        $studentIds = Student::where('tutor_id', $tutorId)->pluck('id');

        // Assigned students
        $assignedStudents = (int) $studentIds->count();

        // Pending reviews: Submitted worklogs where no review exists yet
        $pendingReviewQuery = Worklog::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'Submitted');

        $pendingReviews = (int) $pendingReviewQuery->count();

        // Follow-ups due in next 14 days
        $followupsDue = (int) Followup::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'Scheduled')
            ->whereBetween('scheduled_at', [now(), now()->addDays(14)])
            ->count();

        // Open issues — count only issues assigned to this tutor with status 'Open'
        // (Matches the same filtering logic used in IssueController::stats for tutors)
        $openIssues = (int) Issue::query()
            ->where('tutor_id', $tutorId)
            ->where('status', 'Open')
            ->count();

        // Inactive students: no worklog in 2+ weeks OR no worklogs ever
        $inactiveStudents = Student::where('tutor_id', $tutorId)
            ->whereDoesntHave('worklogs', function ($q) {
                $q->where('submission_date', '>=', now()->subWeeks(2));
            })
            ->count();

        // Recent worklogs
        $recentWorklogs = Worklog::query()
            ->whereIn('student_id', $studentIds)
            ->with(['student:id,first_name,last_name,email,phone', 'attachments'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'week_number' => $w->week_number,
                'status' => $w->status,
                'submission_date' => optional($w->submission_date)->toDateString(),
                'submitted_at' => optional($w->submitted_at ?? $w->created_at)->toISOString(),
                'description' => $w->description,
                'student' => $w->student ? [
                    'id' => $w->student->id,
                    'name' => $w->student->name,
                    'email' => $w->student->email,
                    'phone' => $w->student->phone,
                ] : null,
            ])
            ->values()
            ->all();

        // Upcoming followups
        $followups = Followup::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'Scheduled')
            ->whereBetween('scheduled_at', [now()->subDays(1), now()->addDays(14)])
            ->with('student:id,first_name,last_name,phone')
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get()
            ->map(function ($f) {
                $date = Carbon::parse($f->scheduled_at);
                $relative = $this->relativeDate($date);

                return [
                    'id' => $f->id,
                    'scheduled_at' => $date->toISOString(),
                    'date_label' => $date->format('M j, Y'),
                    'time_label' => $date->format('g:i A'),
                    'relative' => $relative,
                    'type' => $f->type,
                    'notes' => $f->notes,
                    'status' => $f->status,
                    'student' => $f->student ? [
                        'id' => $f->student->id,
                        'name' => $f->student->name,
                        'phone' => $f->student->phone,
                    ] : null,
                ];
            })
            ->values()
            ->all();

        // Open issues list — only issues assigned to this tutor with status 'Open'
        $issues = Issue::query()
            ->where('tutor_id', $tutorId)
            ->where('status', 'Open')
            ->with(['student:id,first_name,last_name,email'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'status' => $i->status,
                'priority' => $i->priority,
                'created_at' => optional($i->created_at)->toISOString(),
                'student' => $i->student ? [
                    'id' => $i->student->id,
                    'name' => $i->student->name,
                    'email' => $i->student->email,
                ] : null,
            ])
            ->values()
            ->all();

        // Recent activity
        $activities = [];

        foreach ($recentWorklogs as $w) {
            $activities[] = [
                'type' => 'worklog',
                'icon' => 'worklog',
                'message' => ($w['student']['name'] ?? 'A student') . ' submitted Worklog #' . $w['week_number'],
                'timestamp' => $w['submitted_at'] ?? $w['submission_date'],
                'reference_id' => $w['id'],
            ];
        }

        foreach ($issues as $issue) {
            $activities[] = [
                'type' => 'issue',
                'icon' => 'issue',
                'message' => 'Issue "' . $issue['title'] . '" opened for ' . ($issue['student']['name'] ?? 'a student'),
                'timestamp' => $issue['created_at'],
                'reference_id' => $issue['id'],
            ];
        }

        foreach ($followups as $f) {
            $activities[] = [
                'type' => 'followup',
                'icon' => 'followup',
                'message' => 'Follow-up scheduled with ' . ($f['student']['name'] ?? 'a student') . ' — ' . $f['type'],
                'timestamp' => $f['scheduled_at'],
                'reference_id' => $f['id'],
            ];
        }

        usort($activities, function ($a, $b) {
            return strcmp($b['timestamp'] ?? '', $a['timestamp'] ?? '');
        });

        $activities = array_slice(array_values($activities), 0, 20);

        return [
            'stats' => [
                'assigned_students' => $assignedStudents,
                'pending_reviews' => $pendingReviews,
                'followups_due' => $followupsDue,
                'open_issues' => $openIssues,
                'inactive_students' => $inactiveStudents,
            ],
            'recent_worklogs' => $recentWorklogs,
            'upcoming_followups' => $followups,
            'open_issues' => $issues,
            'recent_activity' => $activities,
        ];
    }

    private function relativeDate(Carbon $date): string
    {
        $now = Carbon::now();
        $diffInDays = $now->diffInDays($date);

        if ($diffInDays === 0) {
            return $date->isToday() ? 'Today' : 'Tomorrow';
        }

        if ($diffInDays === 1) {
            return 'Tomorrow';
        }

        if ($diffInDays > 1 && $diffInDays <= 7) {
            return 'In ' . $diffInDays . 'd';
        }

        return $date->format('M j');
    }
}
