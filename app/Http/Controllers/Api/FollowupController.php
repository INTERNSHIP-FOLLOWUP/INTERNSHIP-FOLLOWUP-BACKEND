<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FollowupResource;
use App\Models\Followup;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $query = Followup::query()
            ->where('tutor_id', $user->id)
            ->with(['student:id,name,email,phone']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->where('scheduled_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('scheduled_at', '<=', $request->to);
        }

        $followups = $query->latest('scheduled_at')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => FollowupResource::collection($followups->items()),
            'meta' => [
                'total' => $followups->total(),
                'per_page' => $followups->perPage(),
                'current_page' => $followups->currentPage(),
                'last_page' => $followups->lastPage(),
                'from' => $followups->firstItem(),
                'to' => $followups->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'type' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $studentAssigned = Student::where('id', $validated['student_id'])
            ->where('tutor_id', $user->id)
            ->exists();

        if (!$studentAssigned) {
            return response()->json(['message' => 'Student not assigned to you.'], 403);
        }

        $followup = Followup::create([
            'student_id' => $validated['student_id'],
            'tutor_id' => $user->id,
            'type' => $validated['type'],
            'scheduled_at' => $validated['scheduled_at'],
            'notes' => $validated['notes'],
            'status' => 'Scheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Follow-up created successfully.',
            'data' => new FollowupResource($followup->load('student')),
        ], 201);
    }
}
