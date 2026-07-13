<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Worklog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorklogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Worklog::query()->with(['student', 'attachments']);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student) {
                return response()->json(['message' => 'Student profile not found'], 404);
            }
            $query->where('student_id', $student->id);
        } elseif ($user->role->name === 'tutor') {
            $studentIds = Student::where('tutor_id', $user->id)->pluck('id');
            $query->whereIn('student_id', $studentIds);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('week_number')) {
            $query->where('week_number', $request->week_number);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'student') {
            return response()->json(['message' => 'Only students can create worklogs'], 403);
        }

        $student = Student::where('email', $user->email)->first();
        if (!$student) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        $validated = $request->validate([
            'week_number' => 'required|integer|min:1',
            'description' => 'required|string',
            'challenges' => 'nullable|string',
            'submission_date' => 'required|date',
            'status' => 'nullable|string|in:pending,reviewed,approved',
        ]);

        $validated['student_id'] = $student->id;

        $worklog = Worklog::create($validated);

        return response()->json($worklog->load(['student', 'attachments']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $worklog = Worklog::with(['student', 'attachments'])->findOrFail($id);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student || $worklog->student_id !== $student->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } elseif ($user->role->name === 'tutor') {
            $student = Student::findOrFail($worklog->student_id);
            if ($student->tutor_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json($worklog);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $worklog = Worklog::findOrFail($id);

        if ($user->role->name === 'student') {
            $student = Student::where('email', $user->email)->first();
            if (!$student || $worklog->student_id !== $student->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'description' => 'required|string',
                'challenges' => 'nullable|string',
            ]);
        } elseif ($user->role->name === 'tutor') {
            $student = Student::findOrFail($worklog->student_id);
            if ($student->tutor_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'status' => 'required|string|in:pending,reviewed,approved',
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $worklog->update($validated);

        return response()->json($worklog->load(['student', 'attachments']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $worklog = Worklog::findOrFail($id);

        if ($user->role->name !== 'student') {
            return response()->json(['message' => 'Only students can delete worklogs'], 403);
        }

        $student = Student::where('email', $user->email)->first();
        if (!$student || $worklog->student_id !== $student->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $worklog->delete();

        return response()->noContent();
    }
}