<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\InternshipAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyDashboardController extends Controller
{
    /**
     * Get the company linked to the authenticated supervisor.
     */
    private function getCompany(Request $request): ?Company
    {
        $supervisor = CompanySupervisor::where('user_id', $request->user()->id)->first();

        return $supervisor?->company;
    }

    /**
     * Get the company profile linked to the authenticated supervisor.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $company = $this->getCompany($request);

        return response()->json($company);
    }

    /**
     * Update the company profile linked to the authenticated supervisor.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $company = $this->getCompany($request);

        if (!$company) {
            return response()->json(['message' => 'No company assigned to this supervisor.'], 404);
        }

        $validated = $request->validate([
            'company_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'company_name')->ignore($company->id),
            ],
            'address'                => ['nullable', 'string', 'max:255'],
            'industry'               => ['nullable', 'string', 'max:255'],
            'website'                => ['nullable', 'url', 'max:255'],
            'company_profile_image'  => ['nullable', 'string', 'max:255'],
            'telegram_link'          => ['nullable', 'string', 'max:255'],
        ]);

        $company->update($validated);

        return response()->json([
            'message' => 'Company profile updated successfully.',
            'company' => $company->fresh(),
        ]);
    }

    public function students(Request $request)
    {
        $supervisor = CompanySupervisor::where('user_id', $request->user()->id)->first();

        if (!$supervisor || !$supervisor->company_id) {
            return response()->json([
                'data' => [],
                'message' => 'No company assigned to this supervisor.',
            ]);
        }

        $assignments = InternshipAssignment::with(['student.batch', 'tutor'])
            ->whereHas('supervisor', function ($q) use ($supervisor) {
                $q->where('company_id', $supervisor->company_id);
            })
            ->get();

        $students = $assignments->map(function ($assignment) {
            $student = $assignment->student;

            return [
                'id'           => $student?->id,
                'student_name' => $student?->name,
                'student_email'=> $student?->email,
                'batch'        => $student?->batch?->name,
                'position'     => $assignment->position,
                'start_date'   => $assignment->start_date?->format('Y-m-d'),
                'end_date'     => $assignment->end_date?->format('Y-m-d'),
                'status'       => $assignment->status,
                'assignedDate' => $assignment->created_at?->toISOString(),
                'tutor_name'   => $assignment->tutor?->name,
            ];
        });

        return response()->json([
            'data' => $students,
            'message' => 'Assigned students retrieved successfully.',
        ]);
    }
}
