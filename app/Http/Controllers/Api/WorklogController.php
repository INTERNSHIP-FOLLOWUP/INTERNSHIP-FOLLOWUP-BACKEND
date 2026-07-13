<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorklogRequest;
use App\Models\Attachment;
use App\Models\Student;
use App\Models\Worklog;
use App\Services\FileUploadService;
use Illuminate\Http\Request;

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
    public function store(WorklogRequest $request, FileUploadService $uploadService)
    {
        $user = $request->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student) {
            return response()->json([
                'message' => 'Only students can create worklogs.',
            ], 403);
        }

        $worklog = Worklog::create([
            'student_id'      => $student->id,
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
    public function update(WorklogRequest $request, Worklog $worklog, FileUploadService $uploadService)
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
     * Only the owning student can delete, and only if the worklog is still in Draft status.
     */
    public function destroy(Worklog $worklog, FileUploadService $uploadService)
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
     * Remove a single attachment from a worklog.
     * Only the owning student can delete attachments from Draft worklogs.
     */
    public function destroyAttachment(Worklog $worklog, Attachment $attachment, FileUploadService $uploadService)
    {
        $user = request()->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student || $worklog->student_id !== $student->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($worklog->status !== 'Draft') {
            return response()->json([
                'message' => 'Only draft worklogs can be modified.',
            ], 422);
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
