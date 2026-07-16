<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Http\Resources\AssignmentResource;
use App\Models\InternshipAssignment;
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

            return InternshipAssignment::create($data);
        });
    }

    public function update(InternshipAssignment $assignment, array $data): InternshipAssignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            if (isset($data['status'])) {
                $currentStatus = AssignmentStatus::from($assignment->status);
                $newStatus = AssignmentStatus::from($data['status']);

                if (!$currentStatus->canTransitionTo($newStatus)) {
                    abort(422, "Cannot transition from '{$currentStatus->value}' to '{$newStatus->value}'.");
                }
            }

            $assignment->update($data);

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
