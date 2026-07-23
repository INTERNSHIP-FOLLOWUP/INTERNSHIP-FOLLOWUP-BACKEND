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
use Illuminate\Support\Facades\Auth;

class FollowupController extends Controller
{
    /**
     * Resolve the user's ID — tutor_id columns reference users.id, not tutors.id.
     */
    private function resolveTutorId(Authenticatable $user): ?int
    {
        return $user->getAuthIdentifier();
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

        $query = Followup::query()
            ->where('tutor_id', $tutorId)
            ->with(['student:id,first_name,last_name,email,phone', 'company:id,company_name']);

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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
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

    public function store(StoreFollowupRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Tutor profile not found.'], 403);
        }

        $validated = $request->validated();

        $studentAssigned = Student::where('id', $validated['student_id'])
            ->where('tutor_id', $tutorId)
            ->exists();

        if (!$studentAssigned) {
            return response()->json(['message' => 'Student not assigned to you.'], 403);
        }

        $followup = Followup::create([
            'student_id' => $validated['student_id'],
            'tutor_id' => $tutorId,
            'company_id' => $validated['company_id'] ?? null,
            'type' => $validated['meeting_type'],
            'scheduled_at' => $validated['meeting_date'],
            'notes' => $validated['notes'] ?? null,
            'action_items' => $validated['action_items'] ?? null,
            'next_followup' => $validated['next_followup'] ?? null,
            'status' => $validated['status'] ?? 'Scheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Follow-up created successfully.',
            'data' => new FollowupResource($followup->load(['student', 'company'])),
        ], 201);
    }

    public function update(UpdateFollowupRequest $request, Followup $followup): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Tutor profile not found.'], 403);
        }

        if ($followup->tutor_id !== $tutorId) {
            return response()->json(['message' => 'Follow-up not found.'], 404);
        }

        $validated = $request->validated();

        $updateData = [];

        if (isset($validated['student_id'])) {
            $updateData['student_id'] = $validated['student_id'];
        }
        if (array_key_exists('company_id', $validated)) {
            $updateData['company_id'] = $validated['company_id'];
        }
        if (isset($validated['meeting_type'])) {
            $updateData['type'] = $validated['meeting_type'];
        }
        if (isset($validated['meeting_date'])) {
            $updateData['scheduled_at'] = $validated['meeting_date'];
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
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        $followup->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Follow-up updated successfully.',
            'data' => new FollowupResource($followup->load(['student', 'company'])),
        ], 200);
    }

    public function destroy(Request $request, Followup $followup): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Tutor profile not found.'], 403);
        }

        if ($followup->tutor_id !== $tutorId) {
            return response()->json(['message' => 'Follow-up not found.'], 404);
        }

        $followup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Follow-up deleted successfully.',
        ], 200);
    }
}