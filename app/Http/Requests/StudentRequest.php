<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function getStudentModel(): ?Student
    {
        $student = $this->route('student');
        if ($student instanceof Student) {
            return $student;
        }
        if (is_numeric($student)) {
            return Student::find((int) $student) ?? Student::where('user_id', (int) $student)->first();
        }
        return null;
    }

    public function rules(): array
    {
        $studentModel = $this->getStudentModel();
        $studentId = $studentModel?->id;
        $userId = $studentModel?->user_id ?? $studentId;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'student_code' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                Rule::unique('students', 'student_code')->ignore($studentId),
            ],
            'first_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'last_name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'gender' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:Male,Female,Other,male,female,other'],
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'batch_id' => ['nullable', 'integer'],
            'tutor_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
        ];

        if (!$studentId && !$isUpdate) {
            $rules['password'] = ['required', 'string', 'min:6'];
            $rules['password_confirmation'] = ['required', 'string', 'same:password'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
