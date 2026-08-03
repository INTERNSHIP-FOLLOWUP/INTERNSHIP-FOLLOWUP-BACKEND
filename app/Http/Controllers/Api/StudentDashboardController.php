<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentDashboardController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $student = $this->getStudent($request);

        return response()->json([
            'data' => new StudentResource($student->load(['batch', 'tutor'])),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $student = $this->getStudent($request);

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'phone'  => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', Rule::in(['Male', 'Female'])],
        ]);

        $student->update($validated);

        $user = $request->user();
        if ($user->name !== $validated['name']) {
            $user->name = $validated['name'];
            $user->save();
        }

        return response()->json([
            'data' => new StudentResource($student->fresh()->load(['batch', 'tutor'])),
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $student = $this->getStudent($request);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        $path = $request->file('photo')->store('students', 'public');
        $student->update(['photo' => $path]);

        $user = $request->user();
        if ($user->avatar !== $path) {
            $user->avatar = $path;
            $user->save();
        }

        return response()->json([
            'photo_url' => Storage::url($path),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password'          => ['required', 'string'],
            'password'                  => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation'     => ['required', 'string'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors'  => ['current_password' => ['Current password does not match our records.']],
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;

        // Auto-activate student when they change password for the first time
        if ($user->status === 'inactive') {
            $user->status = 'active';
        }

        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)
            ->with(['batch', 'tutor.user'])
            ->first();

        if (!$student) {
            return response()->json([
                'data' => null,
                'message' => 'Student profile not found.',
            ], 404);
        }

        $assignment = \App\Models\InternshipAssignment::where('student_id', $student->id)
            ->with(['supervisor.company'])
            ->latest()
            ->first();

        $worklogQuery = \App\Models\Worklog::where('student_id', $student->id);
        $totalWorklogs = (clone $worklogQuery)->count();
        $submittedWorklogs = (clone $worklogQuery)->where('status', 'Submitted')->count();
        $approvedWorklogs = (clone $worklogQuery)->where('status', 'Approved')->count();

        $pendingReviews = \App\Models\Evaluation::where('student_id', $student->id)->count();

        $openIssues = \App\Models\Issue::where('student_id', $student->id)
            ->where('status', 'Open')
            ->count();

        $recentWorklogs = \App\Models\Worklog::where('student_id', $student->id)
            ->with('attachments')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'week_number' => $w->week_number,
                'status' => $w->status,
                'title' => $w->title,
                'created_at' => $w->created_at,
            ]);

        $tutorFeedback = \App\Models\Worklog::where('student_id', $student->id)
            ->whereNotNull('feedback')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'week_number' => $w->week_number,
                'feedback' => $w->feedback,
                'status' => $w->status,
                'created_at' => $w->created_at,
            ]);

        return response()->json([
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_code' => $student->student_code,
                    'batch' => $student->batch ? [
                        'id' => $student->batch->id,
                        'batch_name' => $student->batch->batch_name,
                        'year' => $student->batch->year,
                    ] : null,
                    'tutor' => $student->tutor ? [
                        'id' => $student->tutor->id,
                        'name' => $student->tutor->name,
                        'email' => $student->tutor->email,
                    ] : null,
                ],
                'internship' => $assignment ? [
                    'id' => $assignment->id,
                    'company_name' => $assignment->supervisor?->company?->company_name ?? 'N/A',
                    'position' => $assignment->position,
                    'status' => $assignment->status,
                    'start_date' => $assignment->start_date,
                    'end_date' => $assignment->end_date,
                ] : null,
                'worklogs' => [
                    'total' => $totalWorklogs,
                    'submitted' => $submittedWorklogs,
                    'approved' => $approvedWorklogs,
                    'recent' => $recentWorklogs,
                ],
                'pending_reviews' => $pendingReviews,
                'open_issues' => $openIssues,
                'tutor_feedback' => $tutorFeedback,
            ],
        ]);
    }

    private function getStudent(Request $request): Student
    {
        return Student::where('user_id', $request->user()->id)->firstOrFail();
    }
}
