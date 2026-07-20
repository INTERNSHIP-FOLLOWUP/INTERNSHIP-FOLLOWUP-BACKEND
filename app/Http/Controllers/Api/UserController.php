<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
                $q->where('name', 'like', "%{$search}%")
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

        return response()->json($user, 201);
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
}
