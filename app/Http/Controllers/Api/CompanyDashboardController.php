<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyDashboardController extends Controller
{
    /**
     * Get the company profile linked to the authenticated user.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $company = Company::where('email', $user->email)->firstOrFail();

        return response()->json($company);
    }

    /**
     * Update the company profile linked to the authenticated user.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $company = Company::where('email', $user->email)->firstOrFail();

        $validated = $request->validate([
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
            'company_profile_image'  => ['nullable', 'string', 'max:255'],
            'telegram_link'          => ['nullable', 'string', 'max:255'],
        ]);

        $company->update($validated);

        return response()->json([
            'company' => $company,
            'message' => 'Company profile updated successfully.',
        ]);
    }
}
