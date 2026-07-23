<?php

namespace App\Http\Requests;

use App\Models\CompanyFeedback;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CompanyFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'message' => ['required', 'string', 'max:5000'],
            'strengths' => ['required', 'array', 'min:1'],
            'strengths.*' => ['required', 'string', 'in:' . implode(',', CompanyFeedback::STRENGTHS)],
            'improvement_areas' => ['required', 'array', 'min:1'],
            'improvement_areas.*' => ['required', 'string', 'in:' . implode(',', CompanyFeedback::IMPROVEMENT_AREAS)],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'strengths.required' => 'Please select at least one strength.',
            'strengths.min' => 'Please select at least one strength.',
            'strengths.*.in' => 'The selected strength is invalid.',
            'improvement_areas.required' => 'Please select at least one area for improvement.',
            'improvement_areas.min' => 'Please select at least one area for improvement.',
            'improvement_areas.*.in' => 'The selected improvement area is invalid.',
            'student_id.required' => 'Please select a student.',
            'student_id.exists' => 'The selected student is invalid.',
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
