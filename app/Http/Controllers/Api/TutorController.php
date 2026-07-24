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
        $query = Tutor::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->per_page, 100) ?: 15;
        $tutors = $query->orderBy('first_name')->paginate($perPage);

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
