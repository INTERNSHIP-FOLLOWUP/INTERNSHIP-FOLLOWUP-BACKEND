<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company');

        return [
            'company_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'company_name')->ignore($companyId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                $companyId ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($companyId),
                Rule::unique('users', 'email'),
            ],
            'password' => [$companyId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'company_profile_image' => ['nullable', 'string', 'max:255'],
            'telegram_link' => ['nullable', 'string', 'max:255'],
        ];
    }
}
