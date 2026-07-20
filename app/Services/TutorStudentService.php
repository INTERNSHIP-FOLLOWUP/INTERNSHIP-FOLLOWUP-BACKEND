<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Worklog;
use App\Models\Issue;
use App\Models\Followup;
use App\Models\InternshipAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class TutorStudentService
{
    public function list(int $tutorId, array $filters = []): LengthAwarePaginator
    {
        $query = Student::query()
            ->where('tutor_id', $tutorId)
            ->with(['batch:id,batch_name,year', 'tutor:id,name,email', 'internshipAssignment:id,student_id,company_id,status,position', 'internshipAssignment.company:id,name']);

        if ($search = Arr::get($filters, 'search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        if ($assignmentStatus = Arr::get($filters, 'status')) {
            $query->whereHas('internshipAssignment', function ($q) use ($assignmentStatus) {
                $q->where('status', $assignmentStatus);
            });
        }

        if ($batchId = Arr::get($filters, 'batch_id')) {
            $query->where('batch_id', $batchId);
        }

        if ($hasOpenIssue = Arr::get($filters, 'has_open_issue')) {
            $query->whereHas('issues', function ($q) {
                $q->whereNotIn('status', ['Resolved', 'Closed']);
            });
        }

        $perPage = (int) Arr::get($filters, 'per_page', 15);
        $paginator = $query->orderByDesc('created_at')->paginate(min($perPage, 100));

        $items = $paginator->getCollection()->map(function ($student) use ($tutorId) {
            $student->setRelation('last_worklog_at', $this->getLastWorklogAt($student->id));
            $student->setRelation('feedback_given', $this->hasFeedbackGiven($student->id));
            $student->setRelation('open_issues_count', $this->getOpenIssuesCount($student->id));
            $student->setRelation('next_followup', $this->getNextFollowup($student->id, $tutorId));

            if ($student->internshipAssignment) {
                $student->setRelation('assignment_status', $student->internshipAssignment->status);
                $student->setRelation('company_name', $student->internshipAssignment->company?->name ?? null);
                $student->setRelation('position', $student->internshipAssignment->position);
            }

            return $student;
        });

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->url($paginator->currentPage())]
        );
    }

    public function get(int $tutorId, int $studentId): ?Student
    {
        $student = Student::query()
            ->where('id', $studentId)
            ->where('tutor_id', $tutorId)
            ->with(['batch:id,batch_name,year', 'tutor:id,name,email', 'worklogs', 'issues', 'evaluations'])
            ->first();

        if (!$student) {
            return null;
        }

        $student->setRelation('last_worklog_at', $this->getLastWorklogAt($student->id));
        $student->setRelation('feedback_given', $this->hasFeedbackGiven($student->id));
        $student->setRelation('open_issues_count', $this->getOpenIssuesCount($student->id));
        $student->setRelation('next_followup', $this->getNextFollowup($student->id, $tutorId));

        return $student;
    }

    public function updateStatus(int $tutorId, int $studentId, string $status): InternshipAssignment
    {
        $assignment = InternshipAssignment::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->firstOrFail();

        $assignment->update(['status' => $status]);

        return $assignment->load(['student:id,name', 'company:id,name']);
    }

    public function isAssigned(int $tutorId, int $studentId): bool
    {
        return Student::where('id', $studentId)
            ->where('tutor_id', $tutorId)
            ->exists();
    }

    private function getLastWorklogAt(int $studentId): ?string
    {
        $w = Worklog::where('student_id', $studentId)->latest('submission_date')->first();
        return $w?->submission_date?->toDateString();
    }

    private function hasFeedbackGiven(int $studentId): bool
    {
        return Worklog::where('student_id', $studentId)
            ->whereNotNull('reviewed_at')
            ->exists();
    }

    private function getOpenIssuesCount(int $studentId): int
    {
        return (int) Issue::where('student_id', $studentId)
            ->whereNotIn('status', ['Resolved', 'Closed'])
            ->count();
    }

    private function getNextFollowup(int $studentId, int $tutorId): ?array
    {
        $f = Followup::query()
            ->where('student_id', $studentId)
            ->where('tutor_id', $tutorId)
            ->where('status', 'Scheduled')
            ->where('scheduled_at', '>=', now()->subDay())
            ->orderBy('scheduled_at')
            ->first();

        if (!$f) {
            return null;
        }

        return [
            'id' => $f->id,
            'scheduled_at' => $f->scheduled_at->toISOString(),
            'date_label' => $f->scheduled_at->format('M j, Y'),
            'time_label' => $f->scheduled_at->format('g:i A'),
            'type' => $f->type,
            'notes' => $f->notes,
            'status' => $f->status,
        ];
    }
}
