<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tutor::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($qq) => $qq->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($qq) => $qq->where('email', 'like', "%{$search}%"));
            });
        }

        $perPage = min((int) $request->per_page, 100) ?: 15;
        $tutors = $query->with('user')->orderBy(
            DB::raw('(SELECT CONCAT(first_name, \' \', last_name) FROM users WHERE users.id = tutors.user_id)')
        )->paginate($perPage);

        return response()->json([
            'data' => $tutors->items(),
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
