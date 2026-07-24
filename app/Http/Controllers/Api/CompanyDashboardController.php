<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\InternshipAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyDashboardController extends Controller
{
    /**
     * Get the company linked to the authenticated supervisor.
     */
    private function getCompany(Request $request): Company
    {
        $supervisor = CompanySupervisor::where('user_id', $request->user()->id)->firstOrFail();

        return $supervisor->company;
    }

    /**
     * Get the company profile linked to the authenticated supervisor.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $company = $this->getCompany($request);

        return response()->json([
            'company' => $company,
            'user'    => $user,
        ]);
    }

    /**
     * Update the company profile linked to the authenticated supervisor.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $company = $this->getCompany($request);

        $rules = [
            'company_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'company_name')->ignore($company->id),
            ],
            'address'                => ['nullable', 'string', 'max:255'],
            'industry'               => ['nullable', 'string', 'max:255'],
            'contact_person'         => ['required', 'string', 'max:255'],
            'phone'                  => ['nullable', 'string', 'max:50'],
            'website'                => ['nullable', 'url', 'max:255'],
            'telegram_link'          => ['nullable', 'string', 'max:255'],
        ];

        // Conditional validation: file upload vs URL string
        if ($request->hasFile('company_image')) {
            $rules['company_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        } else {
            $rules['company_image'] = ['nullable', 'string', 'max:255'];
        }

        if ($request->hasFile('company_profile_image')) {
            $rules['company_profile_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        } else {
            $rules['company_profile_image'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        // Handle company_image upload
        if ($request->hasFile('company_image')) {
            if ($company->company_image &&
                !str_starts_with($company->company_image, 'http://') &&
                !str_starts_with($company->company_image, 'https://')) {
                Storage::disk('public')->delete($company->company_image);
            }
            $validated['company_image'] = $request->file('company_image')
                ->store('companies', 'public');
        } elseif ($request->filled('company_image')) {
            $validated['company_image'] = $request->input('company_image');
        }

        // Handle company_profile_image upload
        if ($request->hasFile('company_profile_image')) {
            if ($company->company_profile_image &&
                !str_starts_with($company->company_profile_image, 'http://') &&
                !str_starts_with($company->company_profile_image, 'https://')) {
                Storage::disk('public')->delete($company->company_profile_image);
            }
            $validated['company_profile_image'] = $request->file('company_profile_image')
                ->store('avatars', 'public');
        }

        $company->update($validated);
        $user->refresh();

        return response()->json([
            'company' => $company,
            'user'    => $user,
            'message' => 'Company profile updated successfully.',
        ]);
    }

    /**
     * Get the students assigned to the company via internship assignments.
     */
    public function students(Request $request)
    {
        $company = $this->getCompany($request);

        $assignments = InternshipAssignment::with(['student.batch', 'tutor'])
            ->where('company_id', $company->id)
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
