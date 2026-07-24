<?php

namespace App\Http\Requests;

use App\Enums\AssignmentStatus;
use App\Models\InternshipAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class InternshipAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function assignmentId(): mixed
    {
        $assignment = $this->route('internship_assignment') ?? $this->route('assignment');

        return $assignment instanceof InternshipAssignment ? $assignment->getKey() : $assignment;
    }

    public function rules(): array
    {
        $assignmentId = $this->assignmentId();
        $requiredIfCreating = $assignmentId ? 'nullable' : 'required';

        return [
            'student_id' => [$requiredIfCreating, 'exists:students,id'],
            'company_supervisors_id' => [$requiredIfCreating, 'exists:company_supervisors,id'],
            'tutor_id' => [$requiredIfCreating, 'exists:users,id'],
            'position' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', 'in:' . implode(',', AssignmentStatus::values())],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
