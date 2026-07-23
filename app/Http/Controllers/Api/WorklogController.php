<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorklogRequest;
use App\Models\Attachment;
use App\Models\Student;
use App\Models\Worklog;
use App\Services\FileUploadService;
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
        if ($user->role->name === 'admin') {
            // Admins see all worklogs (no filter)
        } else {
            $student = Student::where('email', $user->email)->first();

            if ($student && $user->role->name === 'student') {
                // Students see only their own worklogs
                $query->where('student_id', $student->id);
            } elseif ($user->role->name === 'tutor') {
                // Tutors see worklogs of students assigned to them
                // tutor_id references users.id
                $studentIds = Student::where('tutor_id', $user->id)->pluck('id');
                $query->whereIn('student_id', $studentIds);
            }
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
    public function store(WorklogRequest $request, FileUploadService $uploadService)
    {
        $user = $request->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student && $user->role->name !== 'admin') {
            return response()->json([
                'message' => 'Only students can create worklogs.',
            ], 403);
        }

        // Admin can specify student_id; otherwise use the authenticated student's ID
        $studentId = $student ? $student->id : $request->student_id;

        if (!$studentId) {
            return response()->json([
                'message' => 'The student_id field is required when creating worklogs as admin.',
            ], 422);
        }

        $worklog = Worklog::create([
            'student_id'      => $studentId,
            'week_number'     => $request->week_number,
            'description'     => $request->description,
            'challenges'      => $request->challenges,
            'submission_date' => $request->submission_date,
            'status'          => $request->status ?? 'Draft',
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            $uploadedFiles = [];
            foreach ($request->file('attachments') as $file) {
                $validation = $uploadService->validate($file);

                if (!$validation['valid']) {
                    // Clean up any already-uploaded files
                    foreach ($uploadedFiles as $uploaded) {
                        $uploadService->deleteAttachment($uploaded);
                    }
                    $worklog->delete();

                    return response()->json([
                        'message' => 'Validation error',
                        'errors'  => ['attachments' => [$validation['message']]],
                    ], 422);
                }

                $uploadedFiles[] = $uploadService->storeAndCreateAttachment($file, $worklog->id);
            }
        }

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

        if ($user->role->name !== 'admin') {
            $this->authorizeAccess($user, $worklog);
        }

        return response()->json([
            'data'    => $worklog->load(['student.user', 'attachments']),
            'message' => 'Worklog retrieved successfully.',
        ], 200);
    }

    /**
     * Update the specified worklog.
     * Only the owning student can update, and only if the worklog is in Draft or Rejected status.
     */
    public function update(WorklogRequest $request, Worklog $worklog, FileUploadService $uploadService)
    {
        $user = $request->user();
        $student = Student::where('email', $user->email)->first();

        if ($user->role->name !== 'admin') {
            // Non-admin: only the owning student can update
            if (!$student || $worklog->student_id !== $student->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            if (!in_array($worklog->status, ['Draft', 'Rejected'])) {
                return response()->json([
                    'message' => 'Only draft or rejected worklogs can be updated.',
                ], 422);
            }
        }

        $worklog->update($request->validated());

        // Handle new file uploads
        if ($request->hasFile('attachments')) {
            $uploadedFiles = [];
            foreach ($request->file('attachments') as $file) {
                $validation = $uploadService->validate($file);

                if (!$validation['valid']) {
                    // Clean up any already-uploaded files from this batch
                    foreach ($uploadedFiles as $uploaded) {
                        $uploadService->deleteAttachment($uploaded);
                    }

                    return response()->json([
                        'message' => 'Validation error',
                        'errors'  => ['attachments' => [$validation['message']]],
                    ], 422);
                }

                $uploadedFiles[] = $uploadService->storeAndCreateAttachment($file, $worklog->id);
            }
        }

        return response()->json([
            'data'    => $worklog->load('attachments'),
            'message' => 'Worklog updated successfully.',
        ], 200);
    }

    /**
     * Remove the specified worklog from storage.
     * Only the owning student can delete, and only if the worklog is in Draft or Rejected status.
     */
    public function destroy(Worklog $worklog, FileUploadService $uploadService)
    {
        $user = request()->user();
        $student = Student::where('email', $user->email)->first();

        if ($user->role->name !== 'admin') {
            // Non-admin: only the owning student can delete
            if (!$student || $worklog->student_id !== $student->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            if (!in_array($worklog->status, ['Draft', 'Rejected'])) {
                return response()->json([
                    'message' => 'Only draft or rejected worklogs can be deleted.',
                ], 422);
            }
        }

        // Delete associated files and attachment records
        foreach ($worklog->attachments as $attachment) {
            $uploadService->deleteAttachment($attachment);
        }

        $worklog->delete();

        return response()->json([
            'message' => 'Worklog deleted successfully.',
        ], 200);
    }

    /**
     * Update the status of a worklog (tutor only).
     * Valid transitions: Submitted → Approved, Submitted → Rejected.
     * Only the assigned tutor can update the status.
     */
    public function updateStatus(Request $request, Worklog $worklog)
    {
        $user = $request->user();

        if ($user->role->name !== 'admin') {
            // Non-admin: only assigned tutors can update worklog status
            if ($user->role->name !== 'tutor') {
                return response()->json(['message' => 'Only tutors can update worklog status.'], 403);
            }

            // Check if tutor is assigned to this student
            // tutor_id references users.id
            $isAssigned = Student::where('id', $worklog->student_id)
                ->where('tutor_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                return response()->json(['message' => 'You are not assigned to this student.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'status'   => 'required|in:Approved,Rejected',
            'feedback' => 'nullable|string|max:1000',
        ], [
            'status.required'   => 'The status field is required.',
            'status.in'         => 'The status must be one of: Approved, Rejected.',
            'feedback.max'      => 'Feedback must not exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Validate status transition
        $newStatus = $request->status;
        $currentStatus = $worklog->status;

        $validTransitions = [
            'Submitted' => ['Reviewed', 'Approved', 'Rejected', 'Pending'],
            'Pending'   => ['Submitted'],
            'Rejected'  => ['Submitted'],
            'Approved'  => [],
            'Reviewed'  => ['Approved', 'Rejected'],
            'Draft'     => [],
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus] ?? [])) {
            return response()->json([
                'message' => 'Invalid status transition.',
                'error' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'.",
            ], 422);
        }

        $worklog->update([
            'status'   => $newStatus,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'data'    => $worklog->load(['student.user', 'attachments']),
            'message' => "Worklog status updated to '{$newStatus}' successfully.",
        ], 200);
    }

    /**
     * Remove a single attachment from a worklog.
     * Only the owning student can delete attachments from Draft or Rejected worklogs.
     */
    public function destroyAttachment(Worklog $worklog, Attachment $attachment, FileUploadService $uploadService)
    {
        $user = request()->user();
        $student = Student::where('email', $user->email)->first();

        if ($user->role->name !== 'admin') {
            // Non-admin: only the owning student can delete attachments
            if (!$student || $worklog->student_id !== $student->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            if (!in_array($worklog->status, ['Draft', 'Rejected'])) {
                return response()->json([
                    'message' => 'Only draft or rejected worklogs can be modified.',
                ], 422);
            }
        }

        if ($attachment->worklog_id !== $worklog->id) {
            return response()->json(['message' => 'Attachment does not belong to this worklog.'], 404);
        }

        $uploadService->deleteAttachment($attachment);

        return response()->json([
            'message' => 'Attachment deleted successfully.',
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

        if ($user->role->name === 'student') {
            if (!$student || $worklog->student_id !== $student->id) {
                abort(403, 'Forbidden.');
            }
        } elseif ($user->role->name === 'tutor') {
            // tutor_id references users.id
            $isAssigned = Student::where('id', $worklog->student_id)
                ->where('tutor_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                abort(403, 'Forbidden.');
            }
        }
    }
}
