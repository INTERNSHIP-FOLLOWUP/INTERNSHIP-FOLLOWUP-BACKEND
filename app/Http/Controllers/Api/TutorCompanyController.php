<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorCompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || $user->role?->name !== 'tutor') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $companies = Company::query()
            ->select('id', 'company_name')
            ->orderBy('company_name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'company_name' => $c->company_name,
            ]);

        return response()->json([
            'data' => $companies,
        ]);
    }
}

