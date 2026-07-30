<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('company_supervisor_id') && !$this->has('company_supervisors_id')) {
            $this->merge(['company_supervisors_id' => $this->input('company_supervisor_id')]);
        } elseif ($this->has('company_id') && !$this->has('company_supervisors_id')) {
            $this->merge(['company_supervisors_id' => $this->input('company_supervisor_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'student_id' => 'sometimes|integer|exists:students,id',
            'company_supervisors_id' => 'nullable|integer|exists:company_supervisors,id',
            'meeting_type' => 'sometimes|string|in:Weekly,Monthly,Quarterly,Annual',
            'meeting_date' => 'sometimes|date',
            'notes' => 'sometimes|string|max:5000',
            'action_items' => 'nullable|string|max:5000',
            'next_followup' => 'nullable|date|after_or_equal:meeting_date',
            'status' => 'sometimes|in:Scheduled,Completed,Missed,Cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.integer' => 'The student field must be an integer.',
            'student_id.exists' => 'The selected student does not exist.',
            'company_supervisors_id.exists' => 'The selected supervisor does not exist.',
            'meeting_type.in' => 'The meeting type must be one of: Weekly, Monthly, Quarterly.',
            'meeting_date.date' => 'The meeting date must be a valid date.',
            'notes.max' => 'The notes must not exceed 5000 characters.',
            'action_items.max' => 'The action items must not exceed 5000 characters.',
            'next_followup.date' => 'The next follow-up date must be a valid date.',
            'next_followup.after_or_equal' => 'The next follow-up date must be on or after the meeting date.',
            'status.in' => 'The status must be one of: Scheduled, Completed, Missed, Cancelled.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
