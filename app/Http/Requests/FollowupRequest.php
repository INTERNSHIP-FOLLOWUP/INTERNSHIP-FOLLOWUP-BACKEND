<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class FollowupRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:students,id',
            'tutor_id' => 'required|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
            'meeting_type' => 'required|in:Monthly,Quarterly,Annual,Emergency',
            'meeting_date' => 'required|date',
            'notes' => 'required|string|max:5000',
            'action_items' => 'nullable|string|max:2000',
            'next_followup' => 'nullable|date|after:meeting_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'The student field is required.',
            'student_id.exists' => 'The selected student does not exist.',
            'tutor_id.required' => 'The tutor field is required.',
            'tutor_id.exists' => 'The selected tutor does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'meeting_type.required' => 'The meeting type field is required.',
            'meeting_type.in' => 'The meeting type must be one of: Monthly, Quarterly, Annual, Emergency.',
            'meeting_date.required' => 'The meeting date field is required.',
            'meeting_date.date' => 'The meeting date must be a valid date.',
            'notes.required' => 'The notes field is required.',
            'notes.string' => 'The notes must be a string.',
            'notes.max' => 'The notes may not exceed 5000 characters.',
            'action_items.string' => 'The action items must be a string.',
            'action_items.max' => 'The action items may not exceed 2000 characters.',
            'next_followup.date' => 'The next follow-up must be a valid date.',
            'next_followup.after' => 'The next follow-up must be after the meeting date.',
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