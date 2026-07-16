<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Evaluation::query()->with(['student', 'company']);

        if ($user->role->name === 'company') {
            $query->where('company_id', $user->id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'company') {
            return response()->json(['message' => 'Only company representatives can create evaluations'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'company_id' => 'required|exists:companies,id',
            'technical_skill' => 'required|integer|min:1|max:100',
            'communication' => 'required|integer|min:1|max:100',
            'professionalism' => 'required|integer|min:1|max:100',
            'attendance' => 'required|integer|min:1|max:100',
            'feedback' => 'nullable|string',
        ]);

        $validated['company_id'] = $user->id;

        $evaluation = Evaluation::create($validated);

        return response()->json($evaluation->load(['student', 'company']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::with(['student', 'company'])->findOrFail($id);

        if ($user->role->name === 'company' && $evaluation->company_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($evaluation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::findOrFail($id);

        if ($user->role->name !== 'company') {
            return response()->json(['message' => 'Only company representatives can update evaluations'], 403);
        }

        if ($evaluation->company_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'technical_skill' => 'required|integer|min:1|max:100',
            'communication' => 'required|integer|min:1|max:100',
            'professionalism' => 'required|integer|min:1|max:100',
            'attendance' => 'required|integer|min:1|max:100',
            'feedback' => 'nullable|string',
        ]);

        $evaluation->update($validated);

        return response()->json($evaluation->load(['student', 'company']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::findOrFail($id);

        if ($user->role->name !== 'company') {
            return response()->json(['message' => 'Only company representatives can delete evaluations'], 403);
        }

        if ($evaluation->company_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evaluation->delete();

        return response()->noContent();
    }
}