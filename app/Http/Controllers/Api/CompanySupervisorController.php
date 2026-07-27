<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CompanySupervisorController extends Controller
{
    /**
     * List all supervisors for a given company.
     */
    public function index(Company $company): JsonResponse
    {
        $supervisors = CompanySupervisor::where('company_id', $company->id)
            ->with('user:id,first_name,last_name,email,phone,gender,avatar,last_active_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'user_id' => $s->user_id,
                    'name' => $s->name,
                    'email' => $s->user?->email,
                    'phone' => $s->user?->phone,
                    'first_name' => $s->user?->first_name,
                    'last_name' => $s->user?->last_name,
                    'gender' => $s->user?->gender,
                    'status' => $s->status,
                    'avatar' => $s->user?->avatar_url,
                    'last_active_at' => $s->user?->last_active_at,
                    'created_at' => $s->created_at?->toISOString(),
                ];
            });

        return response()->json([
            'data' => $supervisors,
            'message' => 'Supervisors retrieved successfully.',
        ]);
    }

    /**
     * Create a new supervisor for a company.
     * This creates a User with role 'supervisor' + a CompanySupervisor record.
     */
    public function store(Request $request, Company $company): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $role = Role::where('name', 'supervisor')->first();
        if (!$role) {
            return response()->json([
                'message' => 'Required role "supervisor" not found. Please run database seeders.',
            ], 500);
        }

        // Create the User record for login
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'status' => 'active',
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
            'role_id' => $role->id,
        ]);

        // Save phone/gender to the User record if provided
        $userUpdate = array_filter([
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ]);
        if (!empty($userUpdate)) {
            $user->update($userUpdate);
        }

        // Create the CompanySupervisor record linking to the company
        $supervisor = CompanySupervisor::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        return response()->json([
            'data' => [
                'id' => $supervisor->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'status' => $user->status,
                'created_at' => $supervisor->created_at?->toISOString(),
            ],
            'message' => 'Supervisor created successfully.',
        ], 201);
    }

    /**
     * Show a specific supervisor.
     */
    public function show(Company $company, CompanySupervisor $supervisor): JsonResponse
    {
        if ($supervisor->company_id !== $company->id) {
            return response()->json(['message' => 'Supervisor does not belong to this company.'], 404);
        }

        $supervisor->load('user:id,first_name,last_name,email,phone,gender,avatar,last_active_at,status');

        return response()->json([
            'data' => [
                'id' => $supervisor->id,
                'user_id' => $supervisor->user_id,
                'name' => $supervisor->name,
                'email' => $supervisor->user?->email,
                'first_name' => $supervisor->user?->first_name,
                'last_name' => $supervisor->user?->last_name,
                'phone' => $supervisor->user?->phone,
                'gender' => $supervisor->user?->gender,
                'status' => $supervisor->status,
                'avatar' => $supervisor->user?->avatar_url,
                'last_active_at' => $supervisor->user?->last_active_at,
                'created_at' => $supervisor->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Update a supervisor's details.
     */
    public function update(Request $request, Company $company, CompanySupervisor $supervisor): JsonResponse
    {
        if ($supervisor->company_id !== $company->id) {
            return response()->json(['message' => 'Supervisor does not belong to this company.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($supervisor->user_id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Sync the linked User record
        if ($supervisor->user) {
            $userData = [];
            if (isset($validated['first_name'])) {
                $userData['first_name'] = $validated['first_name'];
            }
            if (isset($validated['last_name'])) {
                $userData['last_name'] = $validated['last_name'];
            }
            if (isset($validated['email'])) {
                $userData['email'] = $validated['email'];
            }
            if (isset($validated['phone'])) {
                $userData['phone'] = $validated['phone'];
            }
            if (isset($validated['gender'])) {
                $userData['gender'] = $validated['gender'];
            }
            if (isset($validated['status'])) {
                $userData['status'] = $validated['status'];
            }
            if (!empty($userData)) {
                $supervisor->user->update($userData);
            }
        }

        return response()->json([
            'data' => $supervisor->fresh()->load('user:id,first_name,last_name,email,phone,gender,avatar'),
            'message' => 'Supervisor updated successfully.',
        ]);
    }

    /**
     * Delete a supervisor (soft-deletes both the CompanySupervisor and User).
     */
    public function destroy(Company $company, CompanySupervisor $supervisor): JsonResponse
    {
        if ($supervisor->company_id !== $company->id) {
            return response()->json(['message' => 'Supervisor does not belong to this company.'], 404);
        }

        // Delete the linked User (which will cascade delete the CompanySupervisor)
        if ($supervisor->user) {
            $supervisor->user->tokens()->delete();
            $supervisor->user->delete();
        }

        $supervisor->delete();

        return response()->json([
            'message' => 'Supervisor deleted successfully.',
        ]);
    }
}
