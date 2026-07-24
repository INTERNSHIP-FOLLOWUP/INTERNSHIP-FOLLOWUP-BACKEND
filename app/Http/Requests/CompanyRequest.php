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

        $rules = [
            'company_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'company_name')->ignore($companyId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'email' => [
                $companyId ? 'nullable' : 'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->ignore($companyId),
            ],
            'website' => ['nullable', 'url', 'max:255'],
            'telegram_link' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->hasFile('company_image')) {
            $rules['company_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        } else {
            $rules['company_image'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->hasFile('company_profile_image')) {
            $rules['company_profile_image'] = ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'];
        } else {
            $rules['company_profile_image'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
