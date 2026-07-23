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
        $role = $user->role?->name;

        $query = Followup::query()->with(['student:id,name']);

        if ($role === 'student') {
            $student = Student::where('user_id', $user->id)->firstOrFail();
            $query->where('student_id', $student->id);
        } elseif ($role === 'tutor') {
            $query->where('tutor_id', $user->id);
        }

        if ($role !== 'student' && $request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('from')) {
            $query->where('meeting_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('meeting_date', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $followups = $query->latest('meeting_date')->latest('id')->paginate($perPage);

        return response()->json([
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
        $role = $user->role?->name;

        $rules = [
            'meeting_type' => ['required', 'string', 'in:Monthly,Quarterly,Annual'],
            'meeting_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'action_items' => ['nullable', 'string'],
            'next_followup' => ['nullable', 'date'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ];

        if ($role === 'tutor') {
            $rules['student_id'] = ['required', 'exists:students,id'];
        }

        $validated = $request->validate($rules);

        if ($role === 'student') {
            $student = Student::where('user_id', $user->id)->firstOrFail();
            $validated['student_id'] = $student->id;
        }

        if ($role === 'tutor') {
            $studentAssigned = Student::where('id', $validated['student_id'])
                ->where('tutor_id', $user->id)
                ->exists();

            if (!$studentAssigned) {
                return response()->json(['message' => 'Student not assigned to you.'], 403);
            }
        }

        $followup = Followup::create([
            'student_id' => $validated['student_id'],
            'tutor_id' => $role === 'tutor' ? $user->id : null,
            'company_id' => $validated['company_id'] ?? null,
            'meeting_type' => $validated['meeting_type'],
            'meeting_date' => $validated['meeting_date'],
            'notes' => $validated['notes'] ?? null,
            'action_items' => $validated['action_items'] ?? null,
            'next_followup' => $validated['next_followup'] ?? null,
        ]);

        return response()->json([
            'data' => new FollowupResource($followup->load(['student'])),
            'message' => 'Follow-up created successfully.',
        ], 201);
    }

    public function show(Followup $followup): JsonResponse
    {
        $user = request()->user();
        $role = $user->role?->name;

        if ($role === 'student') {
            $student = Student::where('user_id', $user->id)->firstOrFail();
            if ($followup->student_id !== $student->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        } elseif ($role === 'tutor' && $followup->tutor_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'data' => new FollowupResource($followup->load(['student'])),
        ]);
    }

    public function update(Request $request, Followup $followup): JsonResponse
    {
        $user = $request->user();
        $role = $user->role?->name;

        if ($role === 'student') {
            $student = Student::where('user_id', $user->id)->firstOrFail();
            if ($followup->student_id !== $student->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        } elseif ($role === 'tutor') {
            if ($followup->tutor_id !== $user->id) {
                return response()->json(['message' => 'Follow-up not found.'], 404);
            }
        }

        $validated = $request->validate([
            'student_id' => ['sometimes', 'required', 'exists:students,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'meeting_type' => ['sometimes', 'required', 'string', 'in:Monthly,Quarterly,Annual'],
            'meeting_date' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string'],
            'action_items' => ['nullable', 'string'],
            'next_followup' => ['nullable', 'date'],

        ]);

        $updateData = [];

        if (isset($validated['student_id'])) {
            $updateData['student_id'] = $validated['student_id'];
        }
        if (array_key_exists('company_id', $validated)) {
            $updateData['company_id'] = $validated['company_id'];
        }
        if (isset($validated['meeting_type'])) {
            $updateData['meeting_type'] = $validated['meeting_type'];
        }
        if (isset($validated['meeting_date'])) {
            $updateData['meeting_date'] = $validated['meeting_date'];
        }
        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }
        if (array_key_exists('action_items', $validated)) {
            $updateData['action_items'] = $validated['action_items'];
        }
        if (array_key_exists('next_followup', $validated)) {
            $updateData['next_followup'] = $validated['next_followup'];
        }
        $followup->update($updateData);

        return response()->json([
            'data' => new FollowupResource($followup->load(['student'])),
            'message' => 'Follow-up updated successfully.',
        ], 200);
    }

    public function destroy(Request $request, Followup $followup): JsonResponse
    {
        $user = $request->user();
        $role = $user->role?->name;

        if ($role === 'student') {
            $student = Student::where('user_id', $user->id)->firstOrFail();
            if ($followup->student_id !== $student->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        } elseif ($role === 'tutor') {
            if ($followup->tutor_id !== $user->id) {
                return response()->json(['message' => 'Follow-up not found.'], 404);
            }
        }

        $followup->delete();

        return response()->json([
            'message' => 'Follow-up deleted successfully.',
        ], 200);
    }
}

