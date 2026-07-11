<?php

namespace App\Http\Controllers\Api;

use App\Models\Tutor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TutorController extends Controller
{
    /**
     * Display a listing of all tutors.
     * Returns non-paginated list with id, name, email only.
     * Excludes soft-deleted users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $tutors = Tutor::tutors()
            ->get(['id', 'name', 'email']);

        return response()->json($tutors);
    }
}
