<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
    
class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', "%{$request->company_name}%");
        }

        if ($request->filled('industry')) {
            $query->where('industry', 'like', "%{$request->industry}%");
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * Store a newly created company in storage.
     */
    public function store(CompanyRequest $request)
    {
        $data = $request->validated();

        // Handle company_image upload
        if ($request->hasFile('company_image')) {
            $data['company_image'] = $request->file('company_image')
                ->store('companies', 'public');
        } elseif ($request->filled('company_image')) {
            $data['company_image'] = $request->input('company_image');
        }

        // Handle company_profile_image upload
        if ($request->hasFile('company_profile_image')) {
            $data['company_profile_image'] = $request->file('company_profile_image')
                ->store('avatars', 'public');
        } elseif ($request->filled('company_profile_image')) {
            $data['company_profile_image'] = $request->input('company_profile_image');
        }

        $company = Company::create($data);

        return response()->json([
            'company' => $company,
            'message' => 'Company created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        return response()->json($company);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->validated();

        // Handle company_image upload
        if ($request->hasFile('company_image')) {
            if ($company->company_image &&
                !str_starts_with($company->company_image, 'http://') &&
                !str_starts_with($company->company_image, 'https://')) {
                Storage::disk('public')->delete($company->company_image);
            }
            $data['company_image'] = $request->file('company_image')
                ->store('companies', 'public');
        } elseif ($request->filled('company_image')) {
            $data['company_image'] = $request->input('company_image');
        }

        // Handle company_profile_image upload
        if ($request->hasFile('company_profile_image')) {
            if ($company->company_profile_image &&
                !str_starts_with($company->company_profile_image, 'http://') &&
                !str_starts_with($company->company_profile_image, 'https://')) {
                Storage::disk('public')->delete($company->company_profile_image);
            }
            $data['company_profile_image'] = $request->file('company_profile_image')
                ->store('avatars', 'public');
        } elseif ($request->filled('company_profile_image')) {
            $data['company_profile_image'] = $request->input('company_profile_image');
        }

        $company->update($data);

        return response()->json([
            'company' => $company->fresh()->load('supervisors'),
            'message' => 'Company updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully.',
        ]);
    }
}
