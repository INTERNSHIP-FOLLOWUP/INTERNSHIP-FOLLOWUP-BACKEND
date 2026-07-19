<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternshipAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\InternshipAssignment;
use App\Models\Student;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $assignments = $this->assignmentService->list($request->only([
            'status',
            'company_id',
            'student_id',
            'per_page',
        ]));

        return response()->json([
            'data' => AssignmentResource::collection($assignments->items()),
            'message' => 'Internship assignments retrieved successfully.',
            'meta' => [
                'total' => $assignments->total(),
                'per_page' => $assignments->perPage(),
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'from' => $assignments->firstItem(),
                'to' => $assignments->lastItem(),
            ],
        ]);
    }

    public function store(InternshipAssignmentRequest $request): JsonResponse
    {
        $assignment = $this->assignmentService->create($request->validated());

        return response()->json([
            'data' => new AssignmentResource($assignment->load(['student', 'company', 'tutor'])),
            'message' => 'Internship assignment created successfully.',
        ], 201);
    }

    public function show(InternshipAssignment $assignment): JsonResponse
    {
        return response()->json([
            'data' => new AssignmentResource($assignment->load(['student', 'company', 'tutor'])),
            'message' => 'Internship assignment retrieved successfully.',
        ]);
    }

    public function update(InternshipAssignmentRequest $request, InternshipAssignment $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->update($assignment, $request->validated());

        return response()->json([
            'data' => new AssignmentResource($assignment),
            'message' => 'Internship assignment updated successfully.',
        ]);
    }

    public function destroy(InternshipAssignment $assignment): JsonResponse
    {
        $this->assignmentService->delete($assignment);

        return response()->json([
            'message' => 'Internship assignment deleted successfully.',
        ]);
    }

    public function myInternship(Request $request): JsonResponse
    {
        $user = $request->user();

        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json([
                'data' => null,
                'message' => 'No internship assignment found.',
            ], 404);
        }

        $assignment = InternshipAssignment::where('student_id', $student->id)
            ->with(['student', 'company', 'tutor'])
            ->latest()
            ->first();

        if (!$assignment) {
            return response()->json([
                'data' => null,
                'message' => 'No internship assignment found.',
            ], 404);
        }

        return response()->json([
            'data' => new AssignmentResource($assignment),
        ]);
    }
}
