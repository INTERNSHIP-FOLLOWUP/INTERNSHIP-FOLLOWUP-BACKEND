<?php

namespace App\Http\Controllers\Api;

use App\Exports\UserImportTemplateExport;
use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Imports\StudentImport;
use App\Imports\UserImport;
use App\Models\InternshipAssignment;
use App\Models\Role;
use App\Models\Student;
use App\Models\Tutor;
use App\Models\User;
use App\Models\Worklog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
                $query->withTutorStudentCount();
            }
            if ($request->role === 'student') {
                $query->with('studentProfile');
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

        if ($request->filled('sort')) {
            match ($request->sort) {
                'name_asc' => $query->orderBy('first_name')->orderBy('last_name'),
                'name_desc' => $query->orderBy('first_name', 'desc')->orderBy('last_name', 'desc'),
                'oldest' => $query->orderBy('created_at'),
                default => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate($request->per_page ?? 15);

        $roleCounts = [
            'admin' => User::whereHas('role', fn($q) => $q->where('name', 'admin'))->count(),
            'tutor' => Tutor::count(),
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
        $user->loadMissing(['role', 'studentProfile', 'tutorProfile']);

        return response()->json($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::where('name', $validated['role'])->first();
        $validated['role_id'] = $role?->id;
        unset($validated['role']);

        $user = User::create($validated);

        if ($validated['role_id'] === Role::where('name', 'student')->first()?->id) {
            Student::create([
                'user_id' => $user->id,
                'student_code' => 'STU' . str_pad((string)$user->id, 4, '0', STR_PAD_LEFT),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'gender' => $validated['gender'] ?? 'N/A',
                'status' => 'active',
            ]);
        } elseif ($validated['role_id'] === Role::where('name', 'tutor')->first()?->id) {
            Tutor::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'status' => 'active',
            ]);
        }

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

    public function destroy(string $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        if ($user->studentProfile) {
            $student = $user->studentProfile;
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $student->delete();
        }

        if ($user->tutorProfile) {
            $user->tutorProfile->delete();
        }

        if ($user->company) {
            $user->company->delete();
        }

        $user->tokens()->delete();
        $user->forceDelete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $currentUserId = $request->user()->id;
        $ids = collect($request->ids)->reject(fn($id) => $id === $currentUserId)->values();

        if ($ids->isEmpty()) {
            return response()->json(['message' => 'No valid users to delete (cannot delete your own account).'], 422);
        }

        $deletedCount = 0;
        $errors = [];

        foreach ($ids as $userId) {
            try {
                // Always use withTrashed to find soft-deleted users
                $user = User::withTrashed()->find($userId);
                
                if (!$user) {
                    $errors[] = "User ID {$userId} not found";
                    continue;
                }

                // If user is a student, delete student record first
                if ($user->studentProfile) {
                    $student = $user->studentProfile;
                    if ($student->photo) {
                        Storage::disk('public')->delete($student->photo);
                    }
                    $student->delete();
                }

                // If user is a tutor, delete tutor record first
                if ($user->tutorProfile) {
                    $user->tutorProfile->delete();
                }

                // If user is a company, delete company record first
                if ($user->company) {
                    $user->company->delete();
                }

                $user->tokens()->delete();
                $user->forceDelete();

                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to delete user ID {$userId}: " . $e->getMessage();
            }
        }

        if ($deletedCount === 0 && !empty($errors)) {
            return response()->json([
                'message' => 'Failed to delete any users',
                'deleted_count' => 0,
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'message' => "Deleted {$deletedCount} user(s) successfully.",
            'deleted_count' => $deletedCount,
            'errors' => $errors,
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

    public function exportExcel()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new StudentImport();
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Import failed due to a critical error.',
                'imported' => 0,
                'failed' => 1,
                'errors' => [
                    ['row' => 0, 'reason' => $e->getMessage()],
                ],
            ], 500);
        }

        $errors = $import->getErrors();
        $importedCount = $import->getImportedCount();
        $failedCount = count($errors);

        $failedRows = array_map(function ($err) {
            return [
                'row' => $err['row'],
                'errors' => [$err['reason']],
                'reason' => $err['reason'],
            ];
        }, $errors);

        return response()->json([
            'message' => 'Import completed.',
            'imported' => $importedCount,
            'failed' => $failedCount,
            'errors' => $errors,
            'success_count' => $importedCount,
            'failed_count' => $failedCount,
            'failed_rows' => $failedRows,
        ]);
    }

    public function importTemplate()
    {
        return Excel::download(new UserImportTemplateExport, 'user-import-template.xlsx');
    }

    public function tutorActivity(string $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            $tutorRecord = Tutor::withTrashed()->find($id);
            if ($tutorRecord && $tutorRecord->user_id) {
                $user = User::withTrashed()->find($tutorRecord->user_id);
            }
        }

        if (!$user) {
            return response()->json(['message' => 'Tutor not found.'], 404);
        }

        $tutor = Tutor::withTrashed()->where('user_id', $user->id)->withCount('students')->first();

        if (!$tutor) {
            return response()->json([
                'tutor' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'students_count' => 0,
                ],
                'students' => [],
                'worklog_stats' => ['total' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0],
                'issues' => [],
                'assignments' => [],
            ]);
        }

        $students = $tutor->students()
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

        $studentIds = $students->pluck('id');

        $counts = Worklog::whereIn('student_id', $studentIds)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted")
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->first();

        $worklogStats = [
            'total' => (int) ($counts->total ?? 0),
            'submitted' => (int) ($counts->submitted ?? 0),
            'approved' => (int) ($counts->approved ?? 0),
            'rejected' => (int) ($counts->rejected ?? 0),
        ];

        $issues = $tutor->issues()
            ->select('id', 'student_id', 'title', 'status', 'priority', 'created_at')
            ->with('student:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        $assignments = InternshipAssignment::where('tutor_id', $tutor->id)
            ->with('company:id,company_name', 'student:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tutor' => [
                'id' => $tutor->user_id,
                'name' => $tutor->name,
                'email' => $tutor->email,
                'students_count' => $tutor->students_count,
            ],
            'students' => $students,
            'worklog_stats' => $worklogStats,
            'issues' => $issues,
            'assignments' => $assignments,
        ]);
    }

    public function activity(string $id): JsonResponse
    {
        $user = User::withTrashed()->find($id);

        if (!$user) {
            $studentRecord = Student::withTrashed()->find($id);
            if ($studentRecord && $studentRecord->user_id) {
                $user = User::withTrashed()->find($studentRecord->user_id);
            }
        }

        if (!$user) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        if (!$user->studentProfile) {
            return response()->json([
                'student' => [
                    'id' => null,
                    'student_code' => null,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => null,
                    'photo' => null,
                    'status' => $user->deleted_at ? 'deactivated' : 'active',
                    'batch' => null,
                ],
                'worklogs' => [],
                'worklog_stats' => ['total' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0],
                'evaluations' => [],
                'average_score' => null,
                'issues' => [],
                'assignment' => null,
            ]);
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
