<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class EvaluationRequest extends FormRequest
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
            'student_id' => ['required', 'exists:students,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'technical_skill' => ['required', 'integer', 'min:1', 'max:100'],
            'communication' => ['required', 'integer', 'min:1', 'max:100'],
            'professionalism' => ['required', 'integer', 'min:1', 'max:100'],
            'attendance' => ['required', 'integer', 'min:1', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:2000'],
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