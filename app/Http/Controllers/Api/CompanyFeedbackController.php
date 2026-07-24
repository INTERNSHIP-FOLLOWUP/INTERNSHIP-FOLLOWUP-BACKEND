<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyFeedbackRequest;
use App\Models\Company;
use App\Models\CompanyFeedback;
use App\Models\Student;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyFeedbackController extends Controller
{
    private function getCompanyId(): int
    {
        $user = Auth::user();
        return Company::where('user_id', $user->id)->value('id')
            ?? throw new \RuntimeException('Company profile not found');
    }

    public function index()
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::with('student')
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return response()->json(['data' => $feedback]);
    }

    public function store(CompanyFeedbackRequest $request)
    {
        $companyId = $this->getCompanyId();

        $feedback = CompanyFeedback::create([
            'company_id' => $companyId,
            'student_id' => $request->student_id,
            'message' => $request->message,
            'strengths' => $request->strengths,
            'improvement_areas' => $request->improvement_areas,
            'title' => $request->title,
        ]);

        return response()->json($feedback, 201);
    }

    public function show(string $id)
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::with('student')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json($feedback);
    }

    public function update(CompanyFeedbackRequest $request, string $id)
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::with('student')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $feedback->update([
            'student_id' => $request->student_id,
            'message' => $request->message,
            'strengths' => $request->strengths,
            'improvement_areas' => $request->improvement_areas,
            'title' => $request->title,
        ]);

        return response()->json($feedback);
    }

    public function destroy(string $id)
    {
        $companyId = $this->getCompanyId();
        $feedback = CompanyFeedback::where('company_id', $companyId)
            ->findOrFail($id);

        $feedback->delete();

        return response()->noContent();
    }

    public function adminIndex()
    {
        $feedback = CompanyFeedback::with(['company', 'student'])
            ->latest()
            ->paginate(15);

        return response()->json($feedback);
    }

    /**
     * Aggregate feedback stats per student.
     *
     * - Tutors see stats for their own students.
     * - Admins see stats for all students (or filtered by student_id).
     *
     * GET /api/tutor/feedback/stats
     * GET /api/admin/feedback/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $query = CompanyFeedback::with('student:id,first_name,last_name,email,student_code,photo');

        // Role-based scoping
        if ($user->role->name === 'tutor') {
            $tutorId = Tutor::where('user_id', $user->id)->value('id');
            if (!$tutorId) {
                return response()->json(['data' => [], 'meta' => [
                    'total_students' => 0,
                    'total_feedback' => 0,
                ]]);
            }
            $studentIds = Student::where('tutor_id', $tutorId)->pluck('id');
            $query->whereIn('student_id', $studentIds);
        }

        // Optional filter: specific student
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $feedback = $query->get();

        // Aggregate per student
        $grouped = $feedback->groupBy('student_id');
        $stats = [];

        foreach ($grouped as $studentId => $entries) {
            $student = $entries->first()->student;
            $strengthCounts = [];
            $improvementCounts = [];
            $latest = null;

            foreach ($entries as $entry) {
                // Track latest submission date
                $created = $entry->created_at;
                if ($latest === null || $created > $latest) {
                    $latest = $created;
                }

                // Count strengths
                if (is_array($entry->strengths)) {
                    foreach ($entry->strengths as $strength) {
                        $strengthCounts[$strength] = ($strengthCounts[$strength] ?? 0) + 1;
                    }
                }

                // Count improvement areas
                if (is_array($entry->improvement_areas)) {
                    foreach ($entry->improvement_areas as $area) {
                        $improvementCounts[$area] = ($improvementCounts[$area] ?? 0) + 1;
                    }
                }
            }

            // Sort by count descending, take top 5
            arsort($strengthCounts);
            arsort($improvementCounts);

            $stats[] = [
                'student' => $student ? [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'student_code' => $student->student_code,
                ] : ['id' => $studentId, 'name' => 'Unknown', 'email' => null, 'student_code' => null],
                'total_feedback' => $entries->count(),
                'strengths_summary' => collect($strengthCounts)
                    ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
                    ->sortByDesc('count')
                    ->values()
                    ->take(10)
                    ->all(),
                'improvement_areas_summary' => collect($improvementCounts)
                    ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
                    ->sortByDesc('count')
                    ->values()
                    ->take(10)
                    ->all(),
                'latest_feedback_at' => optional($latest)->toISOString(),
            ];
        }

        // Sort by latest feedback first
        usort($stats, fn ($a, $b) => strcmp($b['latest_feedback_at'] ?? '', $a['latest_feedback_at'] ?? ''));

        $totalFeedback = $feedback->count();

        return response()->json([
            'data' => $stats,
            'meta' => [
                'total_students' => count($stats),
                'total_feedback' => $totalFeedback,
            ],
        ]);
    }
}
