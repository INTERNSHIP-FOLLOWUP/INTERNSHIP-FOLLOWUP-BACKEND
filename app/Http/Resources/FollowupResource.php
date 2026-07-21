<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'tutor_id' => $this->tutor_id,
            'company_id' => $this->company_id,
            'meeting_type' => $this->type,
            'meeting_date' => optional($this->scheduled_at)->format('Y-m-d'),
            'scheduled_at' => optional($this->scheduled_at)->toISOString(),
            'date_label' => optional($this->scheduled_at)->format('M j, Y'),
            'time_label' => optional($this->scheduled_at)->format('g:i A'),
            'notes' => $this->notes,
            'action_items' => $this->action_items,
            'next_followup' => optional($this->next_followup)->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'student' => $this->whenLoaded('student', fn() => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'email' => $this->student->email,
                'phone' => $this->student->phone,
            ]),
            'company' => $this->whenLoaded('company', fn() => [
                'id' => $this->company->id,
                'company_name' => $this->company->company_name,
                'name' => $this->company->company_name,
            ]),
        ];
    }
}
