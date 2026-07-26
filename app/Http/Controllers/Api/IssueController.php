<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IssueController extends Controller
{
    /**
     * Decode a formatted issue ID (e.g., "ISSUE-001") to its numeric value.
     * If the ID is already numeric, return it as-is.
     */
    private function decodeIssueId(string $id): string
    {
        // Strip "ISSUE-" prefix and leading zeros
        if (preg_match('/^ISSUE-0*(\d+)$/i', $id, $matches)) {
            return $matches[1];
        }
        // If it's already numeric, return as-is
        if (is_numeric($id)) {
            return $id;
        }
        return $id;
    }

    private function resolveTutorId(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        $tutor = \App\Models\Tutor::where('user_id', $user->getAuthIdentifier())->first();
        return $tutor?->id;
    }

    public function index(Request $request)
    {
        $user = $request->user() ?? Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $query = Issue::query()
            ->with(['student', 'tutor', 'reporter', 'assignedUser', 'attachments']);
        if ($user->role?->name === 'student') {
            $student = $user->studentProfile;
            if (!$student) {
                return response()->json(['message' => 'Student profile not found'], 404);
            }
            $query->where(function ($q) use ($student, $user) {
                $q->where('student_id', $student->id)
                  ->orWhere('assigned_user_id', $user->id);
            });
        } elseif ($user->role?->name === 'tutor') {
            $tutorId = $this->resolveTutorId($user);
            $userId = $user->id;
            $query->where(function ($q) use ($tutorId, $userId) {
                $q->where('tutor_id', $tutorId)
                  ->orWhere('assigned_user_id', $userId)
                  ->orWhere('reporter_id', $userId)
                  ->orWhereHas('student', fn($sq) => $sq->where('tutor_id', $tutorId));
            });
        }

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('student.user', fn($sq) => $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $perPage = (int) $request->query('per_page', 6);
        $paginator = $query->orderByDesc('created_at')->paginate(min($perPage, 100));

        return response()->json([
            'data' => IssueResource::collection($paginator->items()),
            'meta' => [
                'totalItems' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
            ],
        ]);
    }

    public function stats(Request $request)
    {
        $user = Auth::user();
        $query = Issue::query();
        if ($user->role->name === 'student') {
            $student = $user->studentProfile;
            if ($student) {
                $query->where(function ($q) use ($student, $user) {
                    $q->where('student_id', $student->id)
                      ->orWhere('assigned_user_id', $user->id);
                });
            }
        } elseif ($user->role?->name === 'tutor') {
            $tutorId = $this->resolveTutorId($user);
            $userId = $user->id;
            $query->where(function ($q) use ($tutorId, $userId) {
                $q->where('tutor_id', $tutorId)
                  ->orWhere('assigned_user_id', $userId)
                  ->orWhere('reporter_id', $userId)
                  ->orWhereHas('student', fn($sq) => $sq->where('tutor_id', $tutorId));
            });
        }

        return response()->json([
            'total' => (int) (clone $query)->count(),
            'open' => (int) (clone $query)->where('status', 'Open')->count(),
            'inProgress' => (int) (clone $query)->where('status', 'In Progress')->count(),
            'resolved' => (int) (clone $query)->whereIn('status', ['Resolved', 'Closed'])->count(),
        ]);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $decodedId = $this->decodeIssueId($id);
        $issue = Issue::with(['student', 'tutor', 'reporter', 'assignedUser', 'attachments', 'history.user'])
            ->findOrFail($decodedId);
        if ($user->role?->name === 'student') {
            $student = $user->studentProfile ?? Student::where('user_id', $user->id)->first();
            if (!$student || ($issue->student_id !== $student->id && $issue->assigned_user_id !== $user->id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role?->name === 'tutor') {
            $tutorId = $this->resolveTutorId($user);
            if ($issue->tutor_id !== $tutorId && $issue->assigned_user_id !== $user->id && $issue->reporter_id !== $user->id) {
                $studentBelongs = Student::where('id', $issue->student_id)->where('tutor_id', $tutorId)->exists();
                if (!$studentBelongs) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }
        }

        return response()->json([
            'data' => new IssueResource($issue),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Build dynamic validation rules based on role
        $validationRules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'nullable|in:Open,In Progress,Resolved,Closed',
            'assigned_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,docx,png,zip|max:10240',
        ];

        // Only tutors and admins can create issues
        if (!in_array($user->role->name, ['tutor', 'admin'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($user->role->name !== 'admin') {
            $validationRules['student_id'] = 'required|exists:students,id';
        }

        $validated = $request->validate($validationRules);

        // Resolve tutor_id and reporter_id based on role
        $tutorId = null;
        $reporterId = $user->id;

        if ($user->role->name === 'tutor') {
            $tutorId = $this->resolveTutorId($user);

            // Verify the student belongs to this tutor
            $student = Student::with('user')->where('id', $validated['student_id'])
                ->where('tutor_id', $tutorId)
                ->first();
            if (!$student) {
                return response()->json(['message' => 'You can only create issues for your own students.'], 403);
            }
        } elseif ($user->role->name === 'admin') {
            $student = Student::with('user')->find($validated['student_id']);
        }

        $issue = DB::transaction(function () use ($validated, $tutorId, $reporterId, $request, $student) {
            $issue = Issue::create([
                'student_id' => $validated['student_id'],
                'reporter_id' => $reporterId,
                'tutor_id' => $tutorId,
                'assigned_user_id' => $validated['assigned_user_id'] ?? $student?->user_id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'] ?? 'Open',
                'priority' => $validated['priority'],
                'due_date' => $validated['due_date'] ?? null,
            ]);

            $issue->history()->create([
                'user_id' => $reporterId,
                'text' => 'Issue created.',
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('issue_attachments', 'public');
                    $issue->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $issue;
        });

        return response()->json(
            new IssueResource($issue->load(['student', 'reporter', 'assignedUser', 'attachments', 'history.user'])),
            201
        );
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $decodedId = $this->decodeIssueId($id);
        $issue = Issue::findOrFail($decodedId);
        if ($user->role->name === 'student') {
            if ($issue->assigned_user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role->name === 'tutor') {
            if ($issue->tutor_id !== $this->resolveTutorId($user)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:Open,In Progress,Resolved,Closed',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
            'assigned_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $changedFields = [];
        foreach ($validated as $key => $value) {
            if ($issue->{$key} !== $value) {
                $changedFields[] = $key;
            }
        }

        $issue->update($validated);

        if (in_array('status', $changedFields) && isset($validated['status'])) {
            $issue->history()->create([
                'user_id' => $user->id,
                'text' => "Status changed to {$validated['status']}",
            ]);
        }

        if (in_array('assigned_user_id', $changedFields) && isset($validated['assigned_user_id'])) {
            $assignedUser = User::find($validated['assigned_user_id']);
            $issue->history()->create([
                'user_id' => $user->id,
                'text' => "Assigned to {$assignedUser?->name}",
            ]);
        }

        return response()->json(
            new IssueResource($issue->load(['student', 'reporter', 'assignedUser', 'attachments', 'history.user']))
        );
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $decodedId = $this->decodeIssueId($id);
        $issue = Issue::findOrFail($decodedId);

        if ($user->role->name !== 'admin') {
            return response()->json(['message' => 'Only admins can delete issues'], 403);
        }

        foreach ($issue->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $issue->delete();

        return response()->noContent();
    }


    public function assign(Request $request, string $id)
    {
        $user = Auth::user();
        $decodedId = $this->decodeIssueId($id);
        $issue = Issue::findOrFail($decodedId);

        if ($user->role->name === 'student' && $issue->assigned_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role->name === 'tutor' && $issue->tutor_id !== $this->resolveTutorId($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'userId' => 'required|exists:users,id',
        ]);

        $oldAssignee = $issue->assigned_user_id;
        $issue->update(['assigned_user_id' => $validated['userId']]);

        $assignedUser = User::find($validated['userId']);
        $issue->history()->create([
            'user_id' => $user->id,
            'text' => $oldAssignee
                ? "Reassigned to {$assignedUser?->name}"
                : "Assigned to {$assignedUser?->name}",
        ]);

        return response()->json(
            new IssueResource($issue->load(['student', 'reporter', 'assignedUser', 'attachments', 'history.user']))
        );
    }

    public function resolve(string $id)
    {
        $user = Auth::user();
        $decodedId = $this->decodeIssueId($id);
        $issue = Issue::findOrFail($decodedId);

        if ($user->role->name === 'student' && $issue->assigned_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role->name === 'tutor' && $issue->tutor_id !== $this->resolveTutorId($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($issue->status === 'Closed') {
            return response()->json(['message' => 'Cannot resolve a closed issue.'], 422);
        }

        $issue->update(['status' => 'Resolved']);
        $issue->history()->create([
            'user_id' => $user->id,
            'text' => 'Issue resolved.',
        ]);

        return response()->json(
            new IssueResource($issue->load(['student', 'reporter', 'assignedUser', 'attachments', 'history.user']))
        );
    }
}

