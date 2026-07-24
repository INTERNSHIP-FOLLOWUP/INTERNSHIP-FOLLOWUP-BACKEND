<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['general', 'assignment', 'validation', 'worklog', 'evaluation', 'followup', 'issue', 'reminder', 'system'])],
            'status' => ['nullable', 'string', Rule::in(['read', 'unread'])],
            'priority' => ['nullable', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string', Rule::in(['newest', 'oldest'])],
        ];
    }
}