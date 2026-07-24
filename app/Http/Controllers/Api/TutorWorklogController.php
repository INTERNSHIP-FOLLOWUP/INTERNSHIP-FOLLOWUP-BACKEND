<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Worklog;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\DB;

class TutorWorklogController extends Controller
{
    /**
     * Resolve the user's ID — tutor_id columns reference users.id, not tutors.id.
     */
    private function resolveTutorId(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        return $user->getAuthIdentifier();
    }

    /**
     * GET /api/tutor/worklogs
     * Return tutor-assigned worklogs (newest first).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Sanctum will return 401 for unauthenticated requests.
        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['success' => true, 'data' => [], 'meta' => [
                'total' => 0, 'per_page' => 15, 'current_page' => 1, 'last_page' => 1, 'from' => null, 'to' => null,
            ]], 200);
        }

        $studentIds = Student::query()
            ->where('tutor_id', $tutorId)
            ->pluck('id');

        $query = Worklog::query()
            ->whereIn('student_id', $studentIds)
            ->with(['student:id,first_name,last_name,email,phone', 'attachments']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('week')) {
            $query->where('week_number', (int) $request->week);
        }

        $worklogs = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $worklogs->items(),
            'meta' => [
                'total' => $worklogs->total(),
                'per_page' => $worklogs->perPage(),
                'current_page' => $worklogs->currentPage(),
                'last_page' => $worklogs->lastPage(),
                'from' => $worklogs->firstItem(),
                'to' => $worklogs->lastItem(),
            ],
        ], 200);
    }

    /**
     * GET /api/tutor/worklogs/{id}
     */
    public function show(Request $request, Worklog $worklog)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Worklog not found.'], 404);
        }

        $studentBelongsToTutor = Student::query()
            ->where('id', $worklog->student_id)
            ->where('tutor_id', $tutorId)
            ->exists();

        if (!$studentBelongsToTutor) {
            return response()->json(['message' => 'Worklog not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $worklog->load(['student:id,first_name,last_name,email,phone', 'attachments']),
        ], 200);
    }

    /**
     * POST /api/tutor/worklogs/{id}
     * Review worklog: {status: approved|rejected|reviewed, feedback?: string}
     */
    public function review(Request $request, Worklog $worklog)
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = $this->resolveTutorId($user);
        if (!$tutorId) {
            return response()->json(['message' => 'Worklog not found.'], 404);
        }

        $studentBelongsToTutor = Student::query()
            ->where('id', $worklog->student_id)
            ->where('tutor_id', $tutorId)
            ->exists();

        if (!$studentBelongsToTutor) {
            return response()->json(['message' => 'Worklog not found.'], 404);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'in:Approved,Rejected,Reviewed,Pending'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        // Feedback-only update: if no status is supplied, keep the current one.
        $dbStatus = $worklog->status;
        if ($request->filled('status')) {
            $dbStatus = match ($validated['status']) {
                'Approved' => 'Approved',
                'Rejected' => 'Rejected',
                'Reviewed' => 'Reviewed',
                'Pending' => 'Pending',
                default => throw new \InvalidArgumentException('Invalid status.'),
            };
        }

        $updated = DB::transaction(function () use ($worklog, $validated, $user, $dbStatus) {
            $worklog->status = $dbStatus;
            $worklog->feedback = $validated['feedback'] ?? $worklog->feedback;
            $worklog->reviewer_id = $user->id;
            $worklog->reviewed_at = now();
            $worklog->save();

            return $worklog->load(['student:id,first_name,last_name,email,phone', 'attachments']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Worklog reviewed successfully.',
            'data' => $updated,
        ], 200);
    }
}

