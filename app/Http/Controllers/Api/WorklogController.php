<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Http\Requests\WorklogRequest;
use App\Models\Attachment;
use App\Models\Student;
use App\Models\Worklog;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
=======
use App\Models\Student;
use App\Models\Worklog;
use App\Services\FileUploadService;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
>>>>>>> feature/evaluation-issue

class WorklogController extends Controller
{
    /**
<<<<<<< HEAD
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
        if ($user->role === 'admin') {
            // Admins see all worklogs (no filter)
        } else {
            $student = Student::where('email', $user->email)->first();

            if ($student && $user->role === 'student') {
                // Students see only their own worklogs
                $query->where('student_id', $student->id);
            } elseif ($user->role === 'tutor') {
                // Tutors see worklogs of students assigned to them
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
=======
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
>>>>>>> feature/evaluation-issue
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

<<<<<<< HEAD
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

        if (!$student && $user->role !== 'admin') {
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

        if ($user->role !== 'admin') {
            $this->authorizeAccess($user, $worklog);
        }

        return response()->json([
            'data'    => $worklog->load(['student', 'attachments']),
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

        if ($user->role !== 'admin') {
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

        if ($user->role !== 'admin') {
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
=======
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
>>>>>>> feature/evaluation-issue
        }

        $worklog->delete();

<<<<<<< HEAD
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

        if ($user->role !== 'admin') {
            // Non-admin: only assigned tutors can update worklog status
            if ($user->role !== 'tutor') {
                return response()->json(['message' => 'Only tutors can update worklog status.'], 403);
            }

            // Check if tutor is assigned to this student
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
            'Submitted' => ['Approved', 'Rejected'],
            'Approved' => [],
            'Rejected' => [],
            'Draft' => [],
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
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
            'data'    => $worklog->load(['student', 'attachments']),
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

        if ($user->role !== 'admin') {
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

        if ($user->role === 'student') {
            if (!$student || $worklog->student_id !== $student->id) {
                abort(403, 'Forbidden.');
            }
        } elseif ($user->role === 'tutor') {
            $isAssigned = Student::where('id', $worklog->student_id)
                ->where('tutor_id', $user->id)
                ->exists();

            if (!$isAssigned) {
                abort(403, 'Forbidden.');
            }
        }
=======
        return response()->noContent();
    }

    /**
     * Upload attachment to worklog
     */
    public function uploadAttachment(Request $request, string $worklogId)
    {
        $user = Auth::user();
        $worklog = Worklog::with('student')->findOrFail($worklogId);

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
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        $file = $request->file('file');
        $fileUploadService = new FileUploadService();

        try {
            $uploadResult = $fileUploadService->upload($file);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $attachment = Attachment::create([
            'worklog_id' => $worklog->id,
            'file_path' => $uploadResult['path'],
            'file_type' => $uploadResult['type'],
            'file_size' => $uploadResult['size'],
        ]);

        return response()->json($attachment, 201);
    }

    /**
     * Delete attachment from worklog
     */
    public function deleteAttachment(string $attachmentId)
    {
        $user = Auth::user();
        $attachment = Attachment::with('worklog.student')->findOrFail($attachmentId);
        $worklog = $attachment->worklog;

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
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fileUploadService = new FileUploadService();
        $fileUploadService->delete($attachment->file_path);

        $attachment->delete();

        return response()->noContent();
>>>>>>> feature/evaluation-issue
    }
}
