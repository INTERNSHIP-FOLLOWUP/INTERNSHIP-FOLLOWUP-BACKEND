<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::with(['batch', 'tutor']);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('tutor_id')) {
            $query->where('tutor_id', $request->tutor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->per_page, 100) ?: 15;
        $students = $query->paginate($perPage);

        return response()->json([
            'data' => StudentResource::collection($students->items()),
            'message' => 'Students retrieved successfully.',
            'meta' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'from' => $students->firstItem(),
                'to' => $students->lastItem(),
            ],
        ]);
    }

    public function store(StudentRequest $request): JsonResponse
    {
        $student = Student::create($request->validated());

        return response()->json([
            'data' => new StudentResource($student->load(['batch', 'tutor'])),
            'message' => 'Student created successfully.',
        ], 201);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'data' => new StudentResource($student->load(['batch', 'tutor'])),
            'message' => 'Student retrieved successfully.',
        ]);
    }

    public function update(StudentRequest $request, Student $student): JsonResponse
    {
        $student->update($request->validated());

        return response()->json([
            'data' => new StudentResource($student->fresh()->load(['batch', 'tutor'])),
            'message' => 'Student updated successfully.',
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully.',
        ]);
    }
}
