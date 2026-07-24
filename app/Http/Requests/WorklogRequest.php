<?php

namespace App\Http\Requests;

use App\Models\Worklog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class WorklogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Resolve the current worklog ID from the route for update requests.
     */
    protected function worklogId(): mixed
    {
        $worklog = $this->route('worklog');

        return $worklog instanceof Worklog ? $worklog->getKey() : $worklog;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $worklogId = $this->worklogId();
        $user = $this->user();

        $rules = [
            // Worklog Details
            'week_number'     => $worklogId ? ['sometimes', 'integer', 'min:1', 'max:52'] : ['required', 'integer', 'min:1', 'max:52'],
            'description'     => $worklogId ? ['sometimes', 'string'] : ['required', 'string'],
            'challenges'      => ['nullable', 'string'],
            'submission_date' => ['nullable', 'date'],

            // Status (students can only set Draft or Submitted)
            'status'          => ['sometimes', 'in:Draft,Submitted'],

            // Attachments (supports both array and single file with multipart)
            'attachments'     => ['sometimes'],
            'attachments.*'   => ['file'],
        ];

        // Admin can specify student_id when creating worklogs for other students
        if (!$worklogId && $user && $user->role->name === 'admin') {
            $rules['student_id'] = ['required', 'exists:students,id'];
        }

        return $rules;
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'week_number.required' => 'The week number field is required.',
            'week_number.integer'  => 'The week number must be an integer.',
            'week_number.min'      => 'The week number must be at least 1.',
            'week_number.max'      => 'The week number may not exceed 52.',
            'description.required' => 'The description field is required.',
            'description.string'   => 'The description must be a string.',
            'submission_date.required' => 'The submission date field is required.',
            'submission_date.date' => 'The submission date must be a valid date.',
            'status.in'            => 'The status must be one of: Draft, Submitted.',
            'attachments.array'    => 'Attachments must be an array.',
            'attachments.*.file'   => 'Each attachment must be a valid file.',
            'student_id.required'  => 'The student_id field is required for admin.',
            'student_id.exists'    => 'The selected student does not exist.',
        ];
    }

    /**
     * Force API validation errors to return a JSON 422 response.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
