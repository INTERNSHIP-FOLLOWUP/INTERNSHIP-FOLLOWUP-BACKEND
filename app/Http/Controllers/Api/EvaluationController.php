<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    private function getCompanyId(): int
    {
        $user = Auth::user();
        return Company::where('user_id', $user->id)->value('id')
            ?? throw new \RuntimeException('Company profile not found');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Evaluation::query()->with(['student', 'company']);

        if ($user->role->name === 'company') {
            $query->where('company_id', $this->getCompanyId());
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'company') {
            return response()->json(['message' => 'Only company representatives can create evaluations'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'technical_skill' => 'required|integer|min:1|max:100',
            'communication' => 'required|integer|min:1|max:100',
            'professionalism' => 'required|integer|min:1|max:100',
            'attendance' => 'required|integer|min:1|max:100',
            'feedback' => 'nullable|string',
        ]);

        $validated['company_id'] = $this->getCompanyId();

        $evaluation = Evaluation::create($validated);

        return response()->json($evaluation->load(['student', 'company']), 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::with(['student', 'company'])->findOrFail($id);

        if ($user->role->name === 'company') {
            $companyId = $this->getCompanyId();
            if ($evaluation->company_id !== $companyId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json($evaluation);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::findOrFail($id);

        if ($user->role->name !== 'company') {
            return response()->json(['message' => 'Only company representatives can update evaluations'], 403);
        }

        $companyId = $this->getCompanyId();
        if ($evaluation->company_id !== $companyId) {
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

    public function destroy(string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::findOrFail($id);

        if ($user->role->name !== 'company') {
            return response()->json(['message' => 'Only company representatives can delete evaluations'], 403);
        }

        $companyId = $this->getCompanyId();
        if ($evaluation->company_id !== $companyId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evaluation->delete();

        return response()->noContent();
    }
}