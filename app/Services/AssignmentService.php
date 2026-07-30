<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Events\NotificationEvent;
use App\Http\Resources\AssignmentResource;
use App\Models\InternshipAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = InternshipAssignment::with(['student', 'company', 'tutor']);

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($companyId = Arr::get($filters, 'company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($studentId = Arr::get($filters, 'student_id')) {
            $query->where('student_id', $studentId);
        }

        $perPage = (int) Arr::get($filters, 'per_page', 15);

        return $query->paginate(min($perPage, 100));
    }

    public function create(array $data): InternshipAssignment
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = AssignmentStatus::Assigned->value;

            $assignment = InternshipAssignment::create($data);

            // Sync the student's tutor_id to match the assigned tutor
            if (isset($data['student_id'], $data['tutor_id'])) {
                Student::where('id', $data['student_id'])
                    ->update(['tutor_id' => $data['tutor_id']]);
            }

            // Notify the student about the new assignment
            if (isset($data['student_id'])) {
                $student = Student::with('user')->find($data['student_id']);
                if ($student && $student->user) {
                    event(new NotificationEvent(
                        user: $student->user,
                        type: 'assignment',
                        category: 'info',
                        priority: 'medium',
                        title: 'New Internship Assignment',
                        message: "You have been assigned to a new internship at {$assignment->company->company_name}.",
                        actionUrl: "/student/internship",
                        referenceType: 'assignment',
                        referenceId: $assignment->id,
                        metadata: ['company_name' => $assignment->company->company_name, 'position' => $assignment->position],
                        senderType: 'system',
                        senderId: null
                    ));
                }
            }

            return $assignment;
        });
    }

    public function update(InternshipAssignment $assignment, array $data): InternshipAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            // Capture original values before update()
            $originalTutorId = $assignment->getOriginal('tutor_id');
            $originalStatus = $assignment->getOriginal('status');

            if (isset($data['status']) && $data['status'] !== $originalStatus) {
                $currentStatus = AssignmentStatus::from($assignment->status);
                $newStatus = AssignmentStatus::from($data['status']);

                if (!$currentStatus->canTransitionTo($newStatus)) {
                    abort(422, "Cannot transition from '{$currentStatus->value}' to '{$newStatus->value}'.");
                }
            }

            $assignment->update($data);

            // Sync the student's tutor_id when tutor changes
            if (isset($data['tutor_id']) && $data['tutor_id'] !== $originalTutorId) {
                Student::where('id', $assignment->student_id)
                    ->update(['tutor_id' => $data['tutor_id']]);
            }

            // Notify student of status change
            if (isset($data['status']) && $data['status'] !== $originalStatus) {
                $student = $assignment->student->load('user');
                if ($student && $student->user) {
                    event(new NotificationEvent(
                        user: $student->user,
                        type: 'assignment',
                        category: 'info',
                        priority: 'medium',
                        title: 'Internship Assignment Updated',
                        message: "Your internship assignment status has been updated to {$data['status']}.",
                        actionUrl: "/student/internship",
                        referenceType: 'assignment',
                        referenceId: $assignment->id,
                        metadata: ['status' => $data['status']],
                        senderType: 'system',
                        senderId: null
                    ));
                }
            }

            return $assignment->fresh()->load(['student', 'company', 'tutor']);
        });
    }

    public function delete(InternshipAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment) {
            $assignment->delete();
        });
    }
}
