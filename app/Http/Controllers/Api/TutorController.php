<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tutor::query()->withCount('students');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($qq) => $qq->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($qq) => $qq->where('email', 'like', "%{$search}%"));
            });
        }

        $perPage = min((int) $request->per_page, 100) ?: 15;
        $userSubquery = \App\Models\User::select('first_name')->whereColumn('users.id', 'tutors.user_id');
        $tutors = $query->with('user')->orderBy($userSubquery)->paginate($perPage);

        $items = $tutors->map(function ($tutor) {
            $data = $tutor->toArray();
            $data['students_count'] = $tutor->students_count ?? 0;
            return $data;
        });

        return response()->json([
            'data' => $items,
            'message' => 'Tutors retrieved successfully.',
            'meta' => [
                'total' => $tutors->total(),
                'per_page' => $tutors->perPage(),
                'current_page' => $tutors->currentPage(),
                'last_page' => $tutors->lastPage(),
                'from' => $tutors->firstItem(),
                'to' => $tutors->lastItem(),
            ],
        ]);
    }
}
