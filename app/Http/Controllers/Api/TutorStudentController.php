<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\Tutor;
use App\Services\TutorStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TutorStudentController extends Controller
{
    public function __construct(private TutorStudentService $students) {}

    /**
     * Resolve the tutors.id from the authenticated user.
     */
    private function resolveTutorId(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        return Tutor::where('user_id', $user->getAuthIdentifier())->value('id');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0, 'per_page' => 15, 'current_page' => 1,
                    'last_page' => 1, 'from' => null, 'to' => null,
                ],
            ], 200);
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'has_open_issue' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($request->query('per_page') ?? 15);

        $records = $this->students->list($tutorId, [
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'batch_id' => $filters['batch_id'] ?? null,
            'has_open_issue' => $request->boolean('has_open_issue'),
            'per_page' => $perPage,
        ]);

        return response()->json([
            'success' => true,
            'data' => StudentResource::collection($records->items()),
            'meta' => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
            ],
        ], 200);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $student = $this->students->get($tutorId, $id);

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // load relations for detail view
        $student = $student->load([
            'batch:id,batch_name,year',
            'tutor:id,first_name,last_name,email',
            'worklogs' => fn ($q) => $q->latest(),
            'issues' => fn ($q) => $q->latest(),
            'evaluations' => fn ($q) => $q->latest(),
            'internshipAssignment.company:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ], 200);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:Assigned,In Progress,Completed,Terminated'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Student assignment not found.'], 404);
        }

        $student = Student::where('id', $id)
            ->where('tutor_id', $tutorId)
            ->with(['internshipAssignment:id,student_id,company_id,status'])
            ->first();

        if (!$student || !$student->internshipAssignment) {
            return response()->json(['message' => 'Student assignment not found.'], 404);
        }

        $assignment = $this->students->updateStatus($tutorId, $id, $request->input('status'));

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => [
                'student_id' => $id,
                'status' => $assignment->status,
                'company_id' => $assignment->company_id,
                'student' => new StudentResource($student),
            ],
        ], 200);
    }
}

