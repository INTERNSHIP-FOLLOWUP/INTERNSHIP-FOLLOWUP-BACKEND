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

class TutorIssueController extends Controller
{
    /**
     * GET /api/tutor/issues/{id}
     *
     * Fetch a single issue by ID for the authenticated tutor.
     * The tutor must be the assigned tutor for this issue.
     */
    public function show(string $id)
    {
        $user = Auth::user();

        // Ensure user is a tutor
        if ($user->role->name !== 'tutor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $issue = Issue::with(['student', 'tutor', 'reporter', 'assignedUser', 'attachments', 'history.user'])
            ->findOrFail($id);

        // Check tutor permission: can only view issues related to their assigned students
        if ($issue->tutor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => new IssueResource($issue),
        ]);
    }

    /**
     * PUT /api/tutor/issues/{id}
     *
     * Update an issue for the authenticated tutor.
     * The tutor can only update issues assigned to them.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        // Ensure user is a tutor
        if ($user->role->name !== 'tutor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $issue = Issue::with(['student', 'tutor', 'reporter', 'assignedUser', 'attachments'])
            ->findOrFail($id);

        // Check tutor permission: can only edit issues related to their assigned students
        if ($issue->tutor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'status' => 'required|in:Open,In Progress,Resolved,Closed',
            'student_id' => 'required|exists:students,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        // Verify the student belongs to this tutor using the relationship
        $student = $user->tutorStudents()
            ->where('id', $validated['student_id'])
            ->first();

        if (!$student) {
            return response()->json([
                'message' => 'You can only assign issues to your own students.'
            ], 403);
        }

        // Track changed fields for history logging
        $changedFields = [];
        foreach ($validated as $key => $value) {
            if ($issue->{$key} !== $value) {
                $changedFields[] = $key;
            }
        }

        // Update the issue
        DB::transaction(function () use ($issue, $validated, $user, $changedFields) {
            $issue->update($validated);

            // Log status changes to history
            if (in_array('status', $changedFields) && isset($validated['status'])) {
                $issue->history()->create([
                    'user_id' => $user->id,
                    'text' => "Status changed to {$validated['status']}",
                ]);
            }

            // Log assignment changes
            if (in_array('assigned_user_id', $changedFields) && isset($validated['assigned_user_id'])) {
                $assignedUser = User::find($validated['assigned_user_id']);
                $issue->history()->create([
                    'user_id' => $user->id,
                    'text' => $assignedUser
                        ? "Assigned to {$assignedUser->name}"
                        : 'Assigned unassigned',
                ]);
            }

            // Log student changes
            if (in_array('student_id', $changedFields) && isset($validated['student_id'])) {
                $student = Student::find($validated['student_id']);
                $issue->history()->create([
                    'user_id' => $user->id,
                    'text' => $student
                        ? "Student changed to {$student->name}"
                        : 'Student changed',
                ]);
            }

            // Log priority changes
            if (in_array('priority', $changedFields) && isset($validated['priority'])) {
                $issue->history()->create([
                    'user_id' => $user->id,
                    'text' => "Priority changed to {$validated['priority']}",
                ]);
            }

            // Log due date changes
            if (in_array('due_date', $changedFields)) {
                $newDate = $validated['due_date'] ?? 'none';
                $issue->history()->create([
                    'user_id' => $user->id,
                    'text' => "Due date changed to {$newDate}",
                ]);
            }
        });

        $issue->load(['student', 'tutor', 'reporter', 'assignedUser', 'attachments', 'history.user']);

        return response()->json([
            'message' => 'Issue updated successfully',
            'data' => new IssueResource($issue),
        ], 200);
    }
}

