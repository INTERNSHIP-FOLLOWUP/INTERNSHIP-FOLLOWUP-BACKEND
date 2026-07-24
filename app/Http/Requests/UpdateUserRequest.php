<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userParam = $this->route('user');
        $userId = $userParam instanceof \App\Models\User ? $userParam->id : $userParam;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:admin,tutor,student,company'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'student_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'batch_id' => ['sometimes', 'nullable', 'integer', 'exists:batches,id'],
            'tutor_id' => ['sometimes', 'nullable', 'integer'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.max' => 'The first name may not exceed 255 characters.',
            'last_name.max' => 'The last name may not exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'The password must be at least 8 characters.',
            'role.in' => 'The selected role is invalid. Allowed roles: admin, tutor, student, company.',
        ];
    }
}
