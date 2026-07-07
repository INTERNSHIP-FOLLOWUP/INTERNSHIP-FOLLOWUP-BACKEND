<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $request->validate(['role' => 'string|in:admin,tutor,student,company']);

            $query->where('role', $request->role);
        }

        return $query->paginate($request->per_page ?? 15);
    }
}
