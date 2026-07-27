<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
    
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

        $company->update($data);

        return response()->json([
            'company' => $company->fresh()->load('supervisors'),
            'message' => 'Company updated successfully.',
        ]);
    }

    /**
     * Permanently remove the specified resource from storage, along with
     * the linked user accounts of its supervisors. Internship assignments,
     * evaluations, feedback, and company-supervisor records are removed
     * automatically via database cascade.
     */
    public function destroy(Company $company)
    {
        DB::transaction(function () use ($company) {
            $supervisors = CompanySupervisor::withTrashed()
                ->where('company_id', $company->id)
                ->get();

            foreach ($supervisors as $supervisor) {
                $user = User::withTrashed()->find($supervisor->user_id);
                if ($user) {
                    $user->tokens()->delete();
                    $user->forceDelete();
                }
            }

            $company->forceDelete();
        });

        return response()->json([
            'message' => 'Company deleted successfully.',
        ]);
    }
}
