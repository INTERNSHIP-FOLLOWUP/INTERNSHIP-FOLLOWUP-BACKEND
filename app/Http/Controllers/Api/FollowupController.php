<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFollowupRequest;
use App\Http\Requests\UpdateFollowupRequest;
use App\Http\Resources\FollowupResource;
use App\Models\Followup;
use App\Models\Student;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowupController extends Controller
{
    private function resolveTutorId(Authenticatable $user): ?int
    {
        $tutor = \App\Models\Tutor::where('user_id', $user->getAuthIdentifier())->first();
        return $tutor?->id;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->role?->name;

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $query = Followup::query()
            ->with(['student:id,user_id,batch_id,tutor_id', 'tutor:id,user_id', 'tutor.user:id,first_name,last_name,email', 'supervisor.company:id,company_name']);

        if ($user->role?->name === 'tutor') {
            $tutorId = $this->resolveTutorId($user);
            $query->where('tutor_id', $tutorId);
        } elseif ($user->role?->name === 'student') {
            $studentId = $user->studentProfile?->id;
            if ($studentId) {
                $query->where('student_id', $studentId);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->role?->name === 'admin') {
            if ($request->filled('tutor_id')) {
                $query->where('tutor_id', $request->tutor_id);
            }
        }

        if ($request->filled('student_id')) {
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
            $query->whereHas('student.user', function ($q) use ($search) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($request->per_page ?? 15), 100) ?: 15;
        $followups = $query->latest('scheduled_at')->paginate($perPage);

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

    public function show(Request $request, Followup $followup): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role?->name === 'tutor' && $followup->tutor_id !== $this->resolveTutorId($user)) {
            return response()->json(['message' => 'Follow-up not found.'], 404);
        }

        if ($user->role?->name === 'student') {
            $studentId = $user->studentProfile?->id;
            if ($followup->student_id !== $studentId) {
                return response()->json(['message' => 'Follow-up not found.'], 404);
            }
        }

        return response()->json([
            'success' => true,
            'data' => new FollowupResource($followup->load(['student', 'tutor', 'supervisor.company'])),
        ]);
    }

    public function store(StoreFollowupRequest $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->role?->name;

        if (!$user || !in_array($user->role?->name, ['tutor', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validated();
        $tutorId = $user->role?->name === 'tutor' ? $this->resolveTutorId($user) : ($validated['tutor_id'] ?? null);

        if (!$tutorId) {
            return response()->json(['message' => 'Tutor profile not found.'], 403);
        }

        if ($user->role?->name === 'tutor') {
            $studentAssigned = Student::where('id', $validated['student_id'])
                ->where('tutor_id', $tutorId)
                ->exists();

            if (!$studentAssigned) {
                return response()->json(['message' => 'Student not assigned to you.'], 403);
            }
        }

        $followup = Followup::create([
            'student_id' => $validated['student_id'],
            'tutor_id' => $tutorId,
            'company_supervisors_id' => $validated['company_supervisors_id'] ?? null,
            'type' => $validated['meeting_type'],
            'scheduled_at' => $validated['meeting_date'],
            'notes' => $validated['notes'] ?? null,
            'action_items' => $validated['action_items'] ?? null,
            'next_followup' => $validated['next_followup'] ?? null,
            'status' => $validated['status'] ?? 'Scheduled',
        ]);

        return response()->json([
            'data' => new FollowupResource($followup->load(['student'])),
            'message' => 'Follow-up created successfully.',
            'data' => new FollowupResource($followup->load(['student', 'tutor', 'supervisor.company'])),
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

    public function update(UpdateFollowupRequest $request, Followup $followup): JsonResponse
    {
        $user = $request->user();
        $role = $user->role?->name;

        if (!$user || !in_array($user->role?->name, ['tutor', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($user->role?->name === 'tutor' && $followup->tutor_id !== $this->resolveTutorId($user)) {
            return response()->json(['message' => 'Follow-up not found.'], 404);
        }

        $validated = $request->validated();
        $updateData = [];

        if (isset($validated['student_id'])) {
            $updateData['student_id'] = $validated['student_id'];
        }
        if (array_key_exists('company_supervisors_id', $validated)) {
            $updateData['company_supervisors_id'] = $validated['company_supervisors_id'];
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
            'data' => new FollowupResource($followup->load(['student', 'tutor', 'supervisor.company'])),
        ], 200);
    }

    public function destroy(Request $request, Followup $followup): JsonResponse
    {
        $user = $request->user();
        $role = $user->role?->name;

        if (!$user || !in_array($user->role?->name, ['tutor', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($user->role?->name === 'tutor' && $followup->tutor_id !== $this->resolveTutorId($user)) {
            return response()->json(['message' => 'Follow-up not found.'], 404);
        }

        $followup->delete();

        return response()->json([
            'message' => 'Follow-up deleted successfully.',
        ], 200);
    }
}