<?php

namespace App\Http\Requests;

use App\Services\FileUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class AttachmentRequest extends FormRequest
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
            'attachments'   => ['required', 'array', 'min:1'],
            'attachments.*' => [
                'required',
                'file',
                'mimes:' . implode(',', FileUploadService::ALLOWED_EXTENSIONS),
                'max:' . (FileUploadService::MAX_FILE_SIZE / 1024), // Convert bytes to KB
            ],
        ];
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        $maxSizeMB = FileUploadService::MAX_FILE_SIZE / 1024 / 1024;
        $allowedTypes = implode(', ', FileUploadService::ALLOWED_EXTENSIONS);

        return [
            'attachments.required'    => 'At least one attachment is required.',
            'attachments.array'       => 'Attachments must be an array.',
            'attachments.min'         => 'At least one attachment is required.',
            'attachments.*.required'  => 'Each attachment is required.',
            'attachments.*.file'      => 'Each attachment must be a valid file.',
            'attachments.*.mimes'     => "Each attachment must be a file of type: {$allowedTypes}.",
            'attachments.*.max'       => "Each attachment must not exceed {$maxSizeMB}MB in size.",
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
