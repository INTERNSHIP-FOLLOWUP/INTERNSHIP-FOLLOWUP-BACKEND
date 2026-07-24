<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanySupervisor;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    private function getSupervisor(): CompanySupervisor
    {
        $user = Auth::user();
        return CompanySupervisor::where('user_id', $user->id)->firstOrFail();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Evaluation::query()->with(['supervisor.company', 'student']);

        if ($user->role->name === 'supervisor') {
            $supervisor = $this->getSupervisor();
            $query->where('company_supervisors_id', $supervisor->id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('company_supervisors_id')) {
            $query->where('company_supervisors_id', $request->company_supervisors_id);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'supervisor') {
            return response()->json(['message' => 'Only supervisors can create evaluations'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'technical_skill' => 'required|integer|min:1|max:100',
            'communication' => 'required|integer|min:1|max:100',
            'professionalism' => 'required|integer|min:1|max:100',
            'attendance' => 'required|integer|min:1|max:100',
            'feedback' => 'nullable|string',
        ]);

        $supervisor = $this->getSupervisor();
        $validated['company_supervisors_id'] = $supervisor->id;

        $evaluation = Evaluation::create($validated);

        return response()->json($evaluation->load(['supervisor.company', 'student']), 201);
    }

    public function show(string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::with(['supervisor.company', 'student'])->findOrFail($id);

        if ($user->role->name === 'supervisor') {
            $supervisor = $this->getSupervisor();
            if ($evaluation->company_supervisors_id !== $supervisor->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json($evaluation);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::findOrFail($id);

        if ($user->role->name !== 'supervisor') {
            return response()->json(['message' => 'Only supervisors can update evaluations'], 403);
        }

        $supervisor = $this->getSupervisor();
        if ($evaluation->company_supervisors_id !== $supervisor->id) {
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

        return response()->json($evaluation->load(['supervisor.company', 'student']));
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        $evaluation = Evaluation::findOrFail($id);

        if ($user->role->name !== 'supervisor') {
            return response()->json(['message' => 'Only supervisors can delete evaluations'], 403);
        }

        $supervisor = $this->getSupervisor();
        if ($evaluation->company_supervisors_id !== $supervisor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evaluation->delete();

        return response()->noContent();
    }
}
