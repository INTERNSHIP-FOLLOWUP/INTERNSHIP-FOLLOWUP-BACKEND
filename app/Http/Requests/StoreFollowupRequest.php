<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:students,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'meeting_type' => 'required|in:Monthly,Quarterly,Annual',
            'meeting_date' => 'required|date',
            'notes' => 'required|string|max:5000',
            'action_items' => 'nullable|string|max:5000',
            'next_followup' => 'nullable|date|after_or_equal:meeting_date',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'The student field is required.',
            'student_id.exists' => 'The selected student does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'meeting_type.required' => 'The meeting type is required.',
            'meeting_type.in' => 'The meeting type must be one of: Monthly, Quarterly, Annual.',
            'meeting_date.required' => 'The meeting date is required.',
            'meeting_date.date' => 'The meeting date must be a valid date.',
            'notes.required' => 'The notes field is required.',
            'notes.max' => 'The notes must not exceed 5000 characters.',
            'action_items.max' => 'The action items must not exceed 5000 characters.',
            'next_followup.date' => 'The next follow-up date must be a valid date.',
            'next_followup.after_or_equal' => 'The next follow-up date must be on or after the meeting date.',
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
