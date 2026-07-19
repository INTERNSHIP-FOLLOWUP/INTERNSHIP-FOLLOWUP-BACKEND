<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyDashboardController extends Controller
{
    /**
     * Get the company profile linked to the authenticated user.
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $company = Company::where('user_id', $user->id)->firstOrFail();

        return response()->json([
            'company' => $company,
            'user'    => $user,
        ]);
    }

    /**
     * Update the company profile linked to the authenticated user.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $company = Company::where('user_id', $user->id)->firstOrFail();

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

        // Conditional validation: user avatar file upload vs URL string
        if ($request->hasFile('avatar')) {
            $rules['avatar'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        } else {
            $rules['avatar'] = ['nullable', 'string', 'max:255'];
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

        // Handle user avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar &&
                !str_starts_with($user->avatar, 'http://') &&
                !str_starts_with($user->avatar, 'https://')) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        } elseif ($request->filled('avatar')) {
            $user->avatar = $request->input('avatar');
            $user->save();
        }

        $company->update($validated);
        $user->refresh();

        return response()->json([
            'company' => $company,
            'user'    => $user,
            'message' => 'Company profile updated successfully.',
        ]);
    }
}
