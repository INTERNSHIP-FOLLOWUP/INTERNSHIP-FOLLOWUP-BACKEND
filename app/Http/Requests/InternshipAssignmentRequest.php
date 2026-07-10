<?php

namespace App\Http\Requests;

use App\Models\InternshipAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class InternshipAssignmentRequest extends FormRequest
{
    // Determine if the user is authorized to make this request.
    public function authorize(): bool
    {
        return true;
    }

    // Resolve the current assignment ID from the route for update requests.
    protected function assignmentId(): mixed
    {
        $assignment = $this->route('internship_assignment') ?? $this->route('assignment');

        return $assignment instanceof InternshipAssignment ? $assignment->getKey() : $assignment;
    }

    // Get the validation rules that apply to the request.
    public function rules(): array
    {
        $assignmentId = $this->assignmentId();

        return [
            // Foreign Keys (required for creation, optional for update)
            'student_id' => $assignmentId ? ['nullable', 'exists:students,id'] : ['required', 'exists:students,id'],
            'company_id' => $assignmentId ? ['nullable', 'exists:companies,id'] : ['required', 'exists:companies,id'],
            'tutor_id' => $assignmentId ? ['nullable', 'exists:users,id'] : ['required', 'exists:users,id'],
            
            // Assignment Details
            'position' => ['required', 'string', 'max:255'],
            
            // Dates with custom validation
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            
            // Status
            'status' => ['nullable', 'in:Assigned,In Progress,Completed,Terminated'],
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