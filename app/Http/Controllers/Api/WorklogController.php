<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Worklog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorklogController extends Controller
{
    /**
     * Display a listing of worklogs with role-based filtering.
     *
     * - Students see only their own worklogs
     * - Tutors see worklogs of students assigned to them
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Worklog::with('attachments');

        // Role-based filtering
        $student = Student::where('email', $user->email)->first();

        if ($student && $user->role?->name === 'student') {
            // Students see only their own worklogs
            $query->where('student_id', $student->id);
        } elseif ($user->role?->name === 'tutor') {
            // Tutors see worklogs of students assigned to them
            $studentIds = Student::where('tutor_id', $user->id)->pluck('id');
            $query->whereIn('student_id', $studentIds);
        }

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('week_number')) {
            $query->where('week_number', $request->week_number);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $worklogs = $query->latest()->paginate(15);

        return response()->json([
            'data' => $worklogs->items(),
            'message' => 'Worklogs retrieved successfully.',
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
     * Store a newly created worklog.
     * Only students can create worklogs for themselves.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Only students can create worklogs.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'week_number' => 'required|integer|min:1|max:52',
            'description' => 'required|string',
            'challenges'  => 'nullable|string',
            'submission_date' => 'required|date',
            'status'      => 'sometimes|in:Draft,Submitted',
        ], [
            'week_number.required' => 'The week number field is required.',
            'week_number.integer'  => 'The week number must be an integer.',
            'week_number.min'      => 'The week number must be at least 1.',
            'week_number.max'      => 'The week number may not exceed 52.',
            'description.required' => 'The description field is required.',
            'description.string'   => 'The description must be a string.',
            'submission_date.required' => 'The submission date field is required.',
            'submission_date.date' => 'The submission date must be a valid date.',
            'status.in' => 'The status must be one of: Draft, Submitted.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $worklog = Worklog::create([
            'student_id'      => $student->id,
            'week_number'     => $request->week_number,
            'description'     => $request->description,
            'challenges'      => $request->challenges,
            'submission_date' => $request->submission_date,
            'status'          => $request->status ?? 'Draft',
        ]);

        return response()->json([
            'data'    => $worklog->load('attachments'),
            'message' => 'Worklog created successfully.',
        ], 201);
    }

    /**
     * Display the specified worklog.
     */
    public function show(Worklog $worklog)
    {
        $user = request()->user();
        $this->authorizeAccess($user, $worklog);

        return response()->json([
            'data'    => $worklog->load(['student', 'attachments']),
            'message' => 'Worklog retrieved successfully.',
        ], 200);
    }

    /**
     * Update the specified worklog.
     * Only the owning student can update, and only if the worklog is still in Draft status.
     */
    public function update(Request $request, Worklog $worklog)
    {
        $user = $request->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student || $worklog->student_id !== $student->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($worklog->status !== 'Draft') {
            return response()->json([
                'message' => 'Only draft worklogs can be updated.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'week_number'     => 'sometimes|integer|min:1|max:52',
            'description'     => 'sometimes|string',
            'challenges'      => 'nullable|string',
            'submission_date' => 'sometimes|date',
            'status'          => 'sometimes|in:Draft,Submitted',
        ], [
            'week_number.integer' => 'The week number must be an integer.',
            'week_number.min'     => 'The week number must be at least 1.',
            'week_number.max'     => 'The week number may not exceed 52.',
            'description.string'  => 'The description must be a string.',
            'submission_date.date' => 'The submission date must be a valid date.',
            'status.in' => 'The status must be one of: Draft, Submitted.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $worklog->update($request->all());

        return response()->json([
            'data'    => $worklog->load('attachments'),
            'message' => 'Worklog updated successfully.',
        ], 200);
    }

    /**
     * Remove the specified worklog from storage.
     * Only the owning student can delete, and only if the worklog is still in Draft status.
     */
    public function destroy(Worklog $worklog)
    {
        $user = request()->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student || $worklog->student_id !== $student->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($worklog->status !== 'Draft') {
            return response()->json([
                'message' => 'Only draft worklogs can be deleted.',
            ], 422);
        }

        $worklog->delete();

        return response()->json([
            'message' => 'Worklog deleted successfully.',
        ], 200);
    }

    /**
     * Authorize access to a specific worklog based on user role.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function authorizeAccess($user, Worklog $worklog): void
    {
        $student = Student::where('email', $user->email)->first();

        if ($user->role?->name === 'student') {
            if (!$student || $worklog->student_id !== $student->id) {
                abort(403, 'Forbidden.');
            }
        } elseif ($user->role?->name === 'tutor') {
            $isAssigned = Student::where('id', $worklog->student_id)
                ->where('tutor_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                abort(403, 'Forbidden.');
            }
        }
    }
}
