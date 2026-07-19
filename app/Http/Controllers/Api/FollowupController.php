<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFollowupRequest;
use App\Http\Requests\UpdateFollowupRequest;
use App\Models\Followup;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Followup::with(['student', 'tutor']);

        if ($user->role->name === 'student') {
            $query->where('student_id', $request->input('student_id', $user->id));
        } elseif ($user->role->name === 'tutor') {
            $query->where('tutor_id', $request->input('tutor_id', $user->id));
        }

        if ($request->has('student_id') && $user->role->name === 'admin') {
            $query->where('student_id', $request->input('student_id'));
        }
        if ($request->has('tutor_id') && $user->role->name === 'admin') {
            $query->where('tutor_id', $request->input('tutor_id'));
        }

        $followups = $query->latest()->get();

        return response()->json([
            'data' => $followups,
        ]);
    }

    public function store(StoreFollowupRequest $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->validated();

        if ($user->role->name === 'tutor') {
            $data['tutor_id'] = $user->id;
        } elseif ($user->role->name === 'student') {
            $data['student_id'] = $user->id;
            $student = Student::where('user_id', $user->id)->first();
            if ($student && $student->tutor_id) {
                $data['tutor_id'] = $student->tutor_id;
            }
        }

        $followup = Followup::create($data);

        return response()->json([
            'data' => $followup->load(['student', 'tutor']),
            'message' => 'Follow-up created successfully.',
        ], 201);
    }

    public function show(Followup $followup): JsonResponse
    {
        $user = Auth::user();

        if ($user->role->name === 'student' && $followup->student_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if ($user->role->name === 'tutor' && $followup->tutor_id !== $user->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'data' => $followup->load(['student', 'tutor']),
        ]);
    }

    public function update(UpdateFollowupRequest $request, Followup $followup): JsonResponse
    {
        $user = Auth::user();

        if ($user->role->name === 'student') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $followup->update($request->validated());

        return response()->json([
            'data' => $followup->fresh()->load(['student', 'tutor']),
            'message' => 'Follow-up updated successfully.',
        ]);
    }

    public function destroy(Followup $followup): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role->name, ['admin', 'tutor'])) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $followup->delete();

        return response()->json([
            'message' => 'Follow-up deleted successfully.',
        ]);
    }
}
