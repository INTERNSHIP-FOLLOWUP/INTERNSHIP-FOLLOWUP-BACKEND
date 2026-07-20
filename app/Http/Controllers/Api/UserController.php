<?php

namespace App\Http\Controllers\Api;

use App\Exports\UserImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Imports\UserImport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('role');

        if ($request->filled('status')) {
            if ($request->status === 'deactivated') {
                $query->onlyTrashed();
            }
        } else {
            $query->withTrashed();
        }

        if ($request->filled('role')) {
            $request->validate([
                'role' => 'string|in:admin,tutor,student,company',
            ], [
                'role.in' => 'The selected role is invalid. Allowed roles: admin, tutor, student, company.',
            ]);

            $role = Role::where('name', $request->role)->first();
            $query->where('role_id', $role?->id);

            if ($request->role === 'tutor') {
                $query->withCount('tutorStudents');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        $roleCounts = [
            'admin' => User::whereHas('role', fn($q) => $q->where('name', 'admin'))->count(),
            'tutor' => User::whereHas('role', fn($q) => $q->where('name', 'tutor'))->count(),
            'student' => User::whereHas('role', fn($q) => $q->where('name', 'student'))->count(),
            'company' => User::whereHas('role', fn($q) => $q->where('name', 'company'))->count(),
        ];

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
            'counts' => $roleCounts,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->loadMissing(['role', 'studentProfile', 'tutorStudents', 'tutoredAssignments']);

        return response()->json($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::where('name', $validated['role'])->first();
        $validated['role_id'] = $role?->id;
        unset($validated['role']);

        $user = User::create($validated);

        return response()->json($user->load('role'), 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            $validated['role_id'] = $role?->id;
            unset($validated['role']);
        }

        $user->update($validated);

        return response()->json([
            'user' => $user->fresh()->load('role'),
            'message' => 'User updated successfully.',
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        if ($user->trashed()) {
            return response()->json(['message' => 'User is already deactivated.'], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deactivated successfully.',
        ]);
    }

    public function activate(string $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);

        $user->restore();

        return response()->json([
            'user' => $user->fresh()->load('role'),
            'message' => 'User activated successfully.',
        ]);
    }

    public function deactivate(User $user): JsonResponse
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 403);
        }

        if ($user->trashed()) {
            return response()->json(['message' => 'User is already deactivated.'], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deactivated successfully.',
        ]);
    }

    public function resetPassword(Request $request, string $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successfully. All existing tokens have been revoked.',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new UserImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        $failedRows = [];

        foreach ($failures as $failure) {
            $failedRows[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }

        return response()->json([
            'message' => 'Import completed.',
            'success_count' => $import->getImportedCount(),
            'failed_count' => count($failures),
            'failed_rows' => $failedRows,
        ]);
    }

    public function importTemplate()
    {
        return Excel::download(new UserImportTemplateExport, 'user-import-template.xlsx');
    }

    public function tutorActivity(string $id): JsonResponse
    {
        $user = User::withTrashed()->withCount('tutorStudents')->findOrFail($id);

        $students = $user->tutorStudents()
            ->with(['batch:id,batch_name', 'user:id,email,first_name,last_name'])
            ->withCount(['worklogs', 'issues'])
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'student_code' => $s->student_code,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'status' => $s->status,
                'batch' => $s->batch?->batch_name,
                'worklogs_count' => $s->worklogs_count,
                'issues_count' => $s->issues_count,
                'user_id' => $s->user_id,
            ]);

        $worklogStats = [
            'total' => \App\Models\Worklog::whereIn('student_id', $students->pluck('id'))->count(),
            'submitted' => \App\Models\Worklog::whereIn('student_id', $students->pluck('id'))->where('status', 'submitted')->count(),
            'approved' => \App\Models\Worklog::whereIn('student_id', $students->pluck('id'))->where('status', 'approved')->count(),
            'rejected' => \App\Models\Worklog::whereIn('student_id', $students->pluck('id'))->where('status', 'rejected')->count(),
        ];

        $issues = $user->assignedIssues()
            ->select('id', 'student_id', 'title', 'status', 'priority', 'created_at')
            ->with('student:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        $assignments = \App\Models\InternshipAssignment::where('tutor_id', $user->id)
            ->with('company:id,company_name', 'student:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tutor' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'students_count' => $user->tutor_students_count,
            ],
            'students' => $students,
            'worklog_stats' => $worklogStats,
            'issues' => $issues,
            'assignments' => $assignments,
        ]);
    }

    public function activity(string $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->studentProfile) {
            return response()->json(['message' => 'User is not a student.'], 422);
        }

        $student = $user->studentProfile;

        $worklogs = $student->worklogs()
            ->select('id', 'week_number', 'status', 'submission_date', 'feedback', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $evaluations = $student->evaluations()
            ->with('company:id,company_name')
            ->select('id', 'company_id', 'technical_skill', 'communication', 'professionalism', 'attendance', 'overall_score', 'feedback', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $issues = $student->issues()
            ->select('id', 'title', 'status', 'priority', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $assignment = $student->internshipAssignment()
            ->with('company:id,company_name')
            ->first();

        $worklogStats = [
            'total' => $worklogs->count(),
            'submitted' => $worklogs->where('status', 'submitted')->count(),
            'approved' => $worklogs->where('status', 'approved')->count(),
            'rejected' => $worklogs->where('status', 'rejected')->count(),
        ];

        $avgScore = $evaluations->avg('overall_score');

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $student->phone,
                'photo' => $student->photo,
                'status' => $student->status,
                'batch' => $student->batch ? ['id' => $student->batch->id, 'name' => $student->batch->batch_name] : null,
            ],
            'worklogs' => $worklogs,
            'worklog_stats' => $worklogStats,
            'evaluations' => $evaluations,
            'average_score' => $avgScore ? round($avgScore, 1) : null,
            'issues' => $issues,
            'assignment' => $assignment ? [
                'id' => $assignment->id,
                'position' => $assignment->position,
                'start_date' => $assignment->start_date,
                'end_date' => $assignment->end_date,
                'status' => $assignment->status,
                'company' => $assignment->company ? ['id' => $assignment->company->id, 'name' => $assignment->company->name] : null,
            ] : null,
        ]);
    }
}
