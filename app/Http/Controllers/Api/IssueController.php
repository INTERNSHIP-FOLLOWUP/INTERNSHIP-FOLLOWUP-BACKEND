<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IssueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Issue::query()->with(['student', 'tutor']);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student) {
                return response()->json(['message' => 'Student profile not found'], 404);
            }
            $query->where('student_id', $student->id);
        } elseif ($user->role->name === 'tutor') {
            $query->where('tutor_id', $user->id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tutor_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'nullable|in:Open,In Progress,Resolved,Closed',
            'priority' => 'nullable|in:Low,Medium,High,Critical',
        ]);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student || $validated['student_id'] !== $student->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role->name === 'tutor') {
            if ($validated['tutor_id'] !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $issue = Issue::create($validated);

        return response()->json($issue->load(['student', 'tutor']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $issue = Issue::with(['student', 'tutor'])->findOrFail($id);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student || $issue->student_id !== $student->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role->name === 'tutor') {
            if ($issue->tutor_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json($issue);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $issue = Issue::findOrFail($id);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student || $issue->student_id !== $student->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'description' => 'nullable|string',
                'status' => 'nullable|in:Open,In Progress,Resolved,Closed',
            ]);
        } elseif ($user->role->name === 'tutor') {
            if ($issue->tutor_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|in:Open,In Progress,Resolved,Closed',
                'priority' => 'nullable|in:Low,Medium,High,Critical',
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $issue->update($validated);

        return response()->json($issue->load(['student', 'tutor']));
    }

    /**
     * Get issue statistics (counts by status).
     */
    public function stats(Request $request)
    {
        $user = Auth::user();
        $query = Issue::query();

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if ($student) {
                $query->where('student_id', $student->id);
            }
        } elseif ($user->role->name === 'tutor') {
            $query->where('tutor_id', $user->id);
        }

        return response()->json([
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'Open')->count(),
            'inProgress' => (clone $query)->where('status', 'In Progress')->count(),
            'resolved' => (clone $query)->whereIn('status', ['Resolved', 'Closed'])->count(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $issue = Issue::findOrFail($id);

        if ($user->role->name !== 'admin') {
            return response()->json(['message' => 'Only admins can delete issues'], 403);
        }

        $issue->delete();

        return response()->noContent();
    }
}