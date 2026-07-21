<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewWorklogRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in controller/policy.
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }
}


