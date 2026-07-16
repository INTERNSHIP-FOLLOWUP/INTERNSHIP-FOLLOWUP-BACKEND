<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('role');

        $roleName = null;

        if ($request->filled('role')) {
            $request->validate([
                'role' => 'string|in:admin,tutor,student,company',
            ], [
                'role.in' => 'The selected role is invalid. Allowed roles: admin, tutor, student, company.',
            ]);

            $roleName = $request->role;
            $role = Role::where('name', $roleName)->first();
            $query->where('role_id', $role?->id);
        }

        if ($roleName === 'tutor') {
            $query->withCount('tutorStudents');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function show(User $user)
    {
        $user->load(['studentProfile', 'tutorStudents', 'tutoredAssignments']);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,tutor,student,company'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not exceed 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.required' => 'The role field is required.',
            'role.in' => 'The selected role is invalid. Allowed roles: admin, tutor, student, company.',
        ]);

        $role = Role::where('name', $validated['role'])->first();
        $validated['role_id'] = $role?->id;
        unset($validated['role']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:admin,tutor,student,company'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ], [
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.in' => 'The selected role is invalid. Allowed roles: admin, tutor, student, company.',
        ]);

        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            $validated['role_id'] = $role?->id;
            unset($validated['role']);
        }

        $user->update($validated);

        return response()->json([
            'user' => $user,
            'message' => 'User updated successfully.',
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
