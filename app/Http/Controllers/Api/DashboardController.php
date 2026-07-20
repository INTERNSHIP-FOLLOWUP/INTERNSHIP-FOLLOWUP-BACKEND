<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Company;
use App\Models\InternshipAssignment;
use App\Models\Issue;
use App\Models\Student;
use App\Models\User;
use App\Models\Worklog;
use App\Models\Evaluation;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        return Cache::remember('admin_dashboard_stats', 300, function () {
            $totalStudents = Student::count();
            $totalCompanies = Company::count();
            $activeInternships = InternshipAssignment::where('status', 'In Progress')->count();
            $pendingIssues = Issue::where('status', 'Open')->count();

            $assignedStudents = InternshipAssignment::whereIn('status', ['In Progress', 'Completed'])
                ->distinct('student_id')
                ->count('student_id');
            $placementRate = $totalStudents > 0 ? round(($assignedStudents / $totalStudents) * 100) : 0;

            $lastSemesterCount = Cache::remember('admin_dashboard_last_semester', 3600, function () {
                $sixMonthsAgo = now()->subMonths(6);
                return InternshipAssignment::where('created_at', '<=', $sixMonthsAgo)
                    ->distinct('student_id')
                    ->count('student_id');
            });
            $studentTrend = $lastSemesterCount > 0
                ? round((($totalStudents - $lastSemesterCount) / $lastSemesterCount) * 100) . '%'
                : '+0%';

            $lastSemesterCompanies = Cache::remember('admin_dashboard_company_trend', 3600, function () {
                $sixMonthsAgo = now()->subMonths(6);
                return Company::where('created_at', '<=', $sixMonthsAgo)->count();
            });
            $companyTrend = $totalCompanies - $lastSemesterCompanies;

            $companyPlacements = InternshipAssignment::selectRaw('company_id, COUNT(DISTINCT student_id) as count')
                ->with('company:id,company_name')
                ->groupBy('company_id')
                ->get()
                ->map(fn($assignment) => [
                    'name' => $assignment->company?->company_name ?? 'Unknown',
                    'count' => (int) $assignment->count,
                ])
                ->sortByDesc('count')
                ->values();

            $batchEnrollments = Batch::withCount('students')
                ->get()
                ->map(fn($batch) => [
                    'name' => $batch->batch_name,
                    'duration' => $batch->year,
                    'count' => $batch->students_count,
                ]);

            $tutors = User::whereHas('role', fn($q) => $q->where('name', 'tutor'))
                ->withCount('tutorStudents')
                ->get()
                ->map(fn($tutor) => [
                    'id' => $tutor->id,
                    'name' => $tutor->name,
                    'email' => $tutor->email,
                    'studentsCount' => $tutor->tutor_students_count,
                ]);

            $recentWorklogs = Worklog::with('student:id,first_name,last_name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($w) => [
                    'id' => $w->id,
                    'actor' => $w->student?->name ?? 'A student',
                    'action' => 'submitted a worklog for week ' . $w->week_number,
                    'target' => null,
                    'time' => $w->created_at->diffForHumans(),
                    'createdAt' => $w->created_at,
                    'type' => 'worklog',
                ]);

            $recentIssues = Issue::with('student:id,first_name,last_name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($i) => [
                    'id' => $i->id,
                    'actor' => $i->student?->name ?? 'A student',
                    'action' => 'reported an issue: ' . $i->title,
                    'target' => null,
                    'time' => $i->created_at->diffForHumans(),
                    'createdAt' => $i->created_at,
                    'type' => 'issue',
                ]);

            $recentEvaluations = Evaluation::with('student:id,first_name,last_name', 'company:id,company_name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'actor' => $e->company?->company_name ?? 'A company',
                    'action' => 'completed evaluation for',
                    'target' => $e->student?->name ?? 'a student',
                    'time' => $e->created_at->diffForHumans(),
                    'createdAt' => $e->created_at,
                    'type' => 'evaluation',
                ]);

            $recentAssignments = InternshipAssignment::with('student:id,first_name,last_name', 'company:id,company_name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'actor' => $a->student?->name ?? 'A student',
                    'action' => 'was assigned to ' . ($a->company?->company_name ?? 'a company'),
                    'target' => null,
                    'time' => $a->created_at->diffForHumans(),
                    'createdAt' => $a->created_at,
                    'type' => 'assignment',
                ]);

            $recentActivity = collect([...$recentWorklogs, ...$recentIssues, ...$recentEvaluations, ...$recentAssignments])
                ->sortByDesc('createdAt')
                ->take(10)
                ->map(fn($item) => collect($item)->except('createdAt')->toArray())
                ->values();

            return [
                'totalStudents' => $totalStudents,
                'totalCompanies' => $totalCompanies,
                'activeInternships' => $activeInternships,
                'pendingIssues' => $pendingIssues,
                'placementRate' => $placementRate,
                'studentTrend' => $studentTrend,
                'companyTrend' => $companyTrend,
                'companyPlacements' => $companyPlacements,
                'batchEnrollments' => $batchEnrollments,
                'tutors' => $tutors,
                'recentActivity' => $recentActivity,
            ];
        });
    }
}
