<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use App\Services\TutorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorDashboardController extends Controller
{
    public function __construct(private TutorDashboardService $dashboards) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $tutorId = Tutor::where('user_id', $user->getAuthIdentifier())->value('id');
        if (!$tutorId) {
            return response()->json(['message' => 'Tutor profile not found.'], 404);
        }

        $payload = $this->dashboards->getTutorDashboard($tutorId);

        return response()->json([
            'success' => true,
            'data' => $payload,
        ], 200);
    }
}
