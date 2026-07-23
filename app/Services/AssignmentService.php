<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Http\Resources\AssignmentResource;
use App\Models\InternshipAssignment;
use App\Models\Student;
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

            return $assignment;
        });
    }

    public function update(InternshipAssignment $assignment, array $data): InternshipAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            // Capture original values before update() since syncOriginal() is called after
            $originalTutorId = $assignment->getOriginal('tutor_id');

            if (isset($data['status']) && $data['status'] !== $assignment->status) {
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
