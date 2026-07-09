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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Resolve the current student ID from the route for update requests.
     */
    protected function studentId(): mixed
    {
        $student = $this->route('student') ?? $this->route('student_id');

        return $student instanceof Student ? $student->getKey() : $student;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $studentCodeRule = Rule::unique('students', 'student_code');

        if ($studentId = $this->studentId()) {
            $studentCodeRule->ignore($studentId);
        }

        return [
            'student_code' => ['required', 'string', 'max:255', $studentCodeRule],
            'batch_id' => ['nullable', 'exists:batches,id'],
            'tutor_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Force API validation errors to return a JSON 422 response.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
