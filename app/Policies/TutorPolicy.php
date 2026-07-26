<?php

namespace App\Policies;

use App\Models\Tutor;
use App\Models\Worklog;
use App\Models\User;
use App\Models\Student;
use App\Models\Issue;
use App\Models\Followup;
use App\Models\InternshipAssignment;
use Illuminate\Auth\Access\Response;

class TutorPolicy
{
    protected function isTutor(User $user): bool
    {
        return $user->role->name === 'tutor';
    }

    protected function resolveTutorId(User $user): ?int
    {
        $tutor = Tutor::where('user_id', $user->id)->first();
        return $tutor?->id;
    }

    // Worklog policy scoped to tutor-student relationship
    public function viewAny(User $user): bool
    {
        return $this->isTutor($user);
    }

    public function view(User $user, Worklog $worklog): bool
    {
        if (!$this->isTutor($user)) {
            return false;
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return false;
        }

        return Student::query()
            ->where('id', $worklog->student_id)
            ->where('tutor_id', $tutorId)
            ->exists();
    }

    // Worklog review/create feedback
    public function review(User $user, Worklog $worklog): bool
    {
        return $this->view($user, $worklog);
    }

    // Student policy
    public function viewAnyStudent(User $user): bool
    {
        return $this->isTutor($user);
    }

    public function viewStudent(User $user, Student $student): bool
    {
        if (!$this->isTutor($user)) {
            return false;
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return false;
        }

        return $student->tutor_id === $tutorId;
    }

    public function updateStudentStatus(User $user, Student $student): bool
    {
        return $this->viewStudent($user, $student);
    }

    // Issue policy
    public function viewAnyIssue(User $user): bool
    {
        return $this->isTutor($user);
    }

    public function viewIssue(User $user, Issue $issue): bool
    {
        if (!$this->isTutor($user)) {
            return false;
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return false;
        }

        return $issue->tutor_id === $tutorId
            && Student::where('id', $issue->student_id)
                ->where('tutor_id', $tutorId)
                ->exists();
    }

    public function createIssue(User $user): bool
    {
        return $this->isTutor($user);
    }

    public function updateIssue(User $user, Issue $issue): bool
    {
        return $this->viewIssue($user, $issue);
    }

    // Followup policy
    public function viewAnyFollowup(User $user): bool
    {
        return $this->isTutor($user);
    }

    public function viewFollowup(User $user, Followup $followup): bool
    {
        if (!$this->isTutor($user)) {
            return false;
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return false;
        }

        return $followup->tutor_id === $tutorId
            && Student::where('id', $followup->student_id)
                ->where('tutor_id', $tutorId)
                ->exists();
    }

    public function createFollowup(User $user): bool
    {
        return $this->isTutor($user);
    }

    public function updateFollowup(User $user, Followup $followup): bool
    {
        return $this->viewFollowup($user, $followup);
    }

    public function deleteFollowup(User $user, Followup $followup): bool
    {
        return $this->viewFollowup($user, $followup);
    }

    // Assignment policy
    public function viewAssignment(User $user, InternshipAssignment $assignment): bool
    {
        if (!$this->isTutor($user)) {
            return false;
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return false;
        }

        return $assignment->tutor_id === $tutorId;
    }
}
