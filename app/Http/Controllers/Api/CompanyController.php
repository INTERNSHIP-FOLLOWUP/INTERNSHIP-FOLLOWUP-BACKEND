<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
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
     * Store a newly created resource in storage.
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

        // Password is not needed on the Company model
        $companyData = $data;
        unset($companyData['password']);

        $company = Company::create($companyData);

        $role = Role::where('name', 'company')->first();

        if (! $role) {
            return response()->json([
                'message' => 'Required role "company" not found. Please run database seeders.',
            ], 500);
        }

        $user = User::create([
            'name'     => $data['contact_person'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role_id'  => $role->id,
        ]);

        // Link the newly created user back to the company record
        $company->user_id = $user->id;
        $company->save();

        // Refresh the company to include the relationship
        $company->load('user');

        return response()->json([
            'company' => $company,
            'user'    => $user,
            'message' => 'Company and company representative account created successfully.',
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

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $company->update($data);

        // Sync the linked User record when email, password, or contact_person changes
        if ($company->user_id) {
            $user = User::find($company->user_id);
            if ($user) {
                if (isset($data['email'])) {
                    $user->email = $data['email'];
                }
                if (isset($data['password'])) {
                    $user->password = $data['password'];
                }
                if (isset($data['contact_person'])) {
                    $user->name = $data['contact_person'];
                }
                $user->save();
            }
        }

        $company->load('user');

        return response()->json([
            'company' => $company,
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
