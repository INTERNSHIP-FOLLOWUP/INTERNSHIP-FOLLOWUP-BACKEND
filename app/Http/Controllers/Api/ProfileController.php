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
            'gender'     => 'nullable|string|in:Male,Female,Other,male,female,other',
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

        if ($request->filled('first_name')) $user->first_name = $request->first_name;
        if ($request->filled('last_name'))  $user->last_name  = $request->last_name;

        if ($request->filled('name')) {
            $parts = explode(' ', trim($request->name), 2);
            if (!$request->filled('first_name')) $user->first_name = $parts[0] ?? $user->first_name;
            if (!$request->filled('last_name'))  $user->last_name  = $parts[1] ?? $user->last_name;
        }

        if ($request->filled('email'))      $user->email      = $request->email;
        if ($request->has('phone'))         $user->phone      = $request->phone;
        if ($request->has('gender') && $request->gender) {
            $user->gender = ucfirst(strtolower($request->gender));
        }

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
        $user->status = 'active';
        $user->save();

        if ($user->studentProfile) {
            $user->studentProfile->update(['status' => 'active']);
        }
        if ($user->tutorProfile) {
            $user->tutorProfile->update(['status' => 'active']);
        }
        if ($user->supervisorProfile) {
            $user->supervisorProfile->update(['status' => 'active']);
        }

        $user->load(['role', 'studentProfile.batch', 'studentProfile.tutor']);

        return response()->json([
            'message' => 'Password changed successfully.',
            'user'    => $this->formatProfileResponse($user),
        ]);
    }

    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $user->theme = $validated['theme'];
        $user->save();

        return response()->json([
            'message' => 'Theme updated successfully.',
            'theme'   => $user->theme,
        ]);
    }

    public function downloadAvatar(Request $request)
    {
        $user = $request->user();

        if (!$user->avatar) {
            return response()->json(['message' => 'No profile photo found.'], 404);
        }

        $avatarRaw = $user->avatar;

        // Handle full URLs like http://127.0.0.1:8000/storage/avatars/file.png
        if (str_starts_with($avatarRaw, 'http://') || str_starts_with($avatarRaw, 'https://')) {
            // Extract path after /storage/
            $parsed = parse_url($avatarRaw, PHP_URL_PATH); // e.g. /storage/avatars/file.png
            $storedPath = preg_replace('#^/?storage/#', '', ltrim($parsed, '/'));
        } else {
            // Strip any leading /storage/ or storage/ prefix
            $storedPath = preg_replace('#^/?storage/#', '', ltrim($avatarRaw, '/'));
        }

        if (!Storage::disk('public')->exists($storedPath)) {
            return response()->json([
                'message' => 'Photo file not found.',
                'path'    => $storedPath,
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($storedPath);
        $mimeType = Storage::disk('public')->mimeType($storedPath) ?: 'image/jpeg';
        $ext      = pathinfo($storedPath, PATHINFO_EXTENSION) ?: 'jpg';

        // Use the user's name as the download filename
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/u', '_', $user->name ?? 'avatar');
        $filename = "{$safeName}_photo.{$ext}";

        return response()->download($fullPath, $filename, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store',
        ]);
    }
}
