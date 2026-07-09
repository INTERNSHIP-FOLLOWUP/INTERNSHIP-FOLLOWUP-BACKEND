<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studentId = $this->route('student') ?? $this->route('student_id');

        return [
            'student_code' => ['required', 'string', 'max:255', 'unique:students,student_code,' . $studentId],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'tutor_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'photo' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
