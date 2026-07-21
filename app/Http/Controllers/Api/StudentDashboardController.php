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
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    private function getStudent(Request $request): Student
    {
        return Student::where('user_id', $request->user()->id)->firstOrFail();
    }
}
