<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    private function formatProfileResponse($user): array
    {
        $studentProfile = $user->studentProfile;
        $batch = $studentProfile?->batch;
        $tutor = $studentProfile?->tutor;

        return [
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone ?? $studentProfile?->phone,
            'gender'     => $user->gender ?? $studentProfile?->gender,
            'avatar'     => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'photo'      => $user->avatar_url ?? $user->avatar,
            'photo_url'  => $user->avatar_url,
            'role'       => $user->role?->name ?? '',
            'status'     => $user->status ?? $studentProfile?->status ?? 'active',
            'student_code' => $user->student_code ?? $studentProfile?->student_code,
            'batch'      => $batch ? [
                'id' => $batch->id,
                'batch_name' => $batch->batch_name ?? $batch->name ?? '',
                'name' => $batch->batch_name ?? $batch->name ?? '',
            ] : null,
            'tutor'      => $tutor ? [
                'id' => $tutor->id,
                'name' => $tutor->name,
                'email' => $tutor->email,
            ] : null,
            'user'       => [
                'id' => $user->id,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'status' => $user->status,
            ],
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'theme'      => $user->theme ?? 'light',
            'must_change_password' => (bool) $user->must_change_password,
        ];
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'studentProfile.batch', 'studentProfile.tutor']);

        return response()->json($this->formatProfileResponse($user));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'name'       => 'sometimes|string|max:255',
            'email'      => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:50',
            'gender'     => 'nullable|string|in:Male,Female,Other',
            'avatar'     => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        if ($request->filled('name')) {
            $parts = explode(' ', trim($request->name), 2);
            $user->first_name = $parts[0] ?? $user->first_name;
            $user->last_name  = $parts[1] ?? $user->last_name;
        }

        if ($request->filled('first_name')) $user->first_name = $request->first_name;
        if ($request->filled('last_name'))  $user->last_name  = $request->last_name;
        if ($request->filled('email'))      $user->email      = $request->email;
        if ($request->has('phone'))         $user->phone      = $request->phone;
        if ($request->has('gender'))        $user->gender     = $request->gender;

        $user->save();

        if ($user->studentProfile) {
            $studentUpdate = [];
            if ($request->has('phone'))  $studentUpdate['phone']  = $request->phone;
            if ($request->has('gender')) $studentUpdate['gender'] = $request->gender;
            if (!empty($studentUpdate)) {
                $user->studentProfile->update($studentUpdate);
            }
        }

        $user->load(['role', 'studentProfile.batch', 'studentProfile.tutor']);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $this->formatProfileResponse($user),
            'data'    => $this->formatProfileResponse($user),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors'  => ['current_password' => ['The current password does not match our records.']]
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => 'required|in:light,dark',
        ]);

        $user = $request->user();
        $user->theme = $validated['theme'];
        $user->save();

        return response()->json([
            'message' => 'Theme updated successfully.',
            'theme'   => $user->theme,
        ]);
    }
}
