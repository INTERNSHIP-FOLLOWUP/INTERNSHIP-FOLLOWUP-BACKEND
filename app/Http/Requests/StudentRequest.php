<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    // Determine if the user is authorized to make this request.
    public function authorize(): bool
    {
        return true;
    }

    // Resolve the current student ID from the route for update requests.
    protected function studentId(): mixed
    {
        $student = $this->route('student') ?? $this->route('student_id');

        return $student instanceof Student ? $student->getKey() : $student;
    }
    // Get the validation rules that apply to the request.
    public function rules(): array
    {
        $studentId = $this->studentId();
        $studentCodeRule = Rule::unique('students', 'student_code');

        if ($studentId) {
            $studentCodeRule->ignore($studentId);
        }

        return [
            // Student Code (Unique validation with ignore on update)
            'student_code' => ['required', 'string', 'max:255', $studentCodeRule],

            // Foreign Keys
            'batch_id'     => ['nullable', 'exists:batches,id'],
            'tutor_id'     => ['nullable', 'exists:users,id'],

            // Basic Info
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'status'       => ['nullable', 'string', 'max:50'],

            // Photo Upload (JPG/PNG formats only as per requirements)
            'photo'        => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    // Force API validation errors to return a JSON 422 response.
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
