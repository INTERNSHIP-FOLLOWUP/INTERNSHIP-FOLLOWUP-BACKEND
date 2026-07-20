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
            'type' => $this->type,
            'scheduled_at' => optional($this->scheduled_at)->toISOString(),
            'date_label' => optional($this->scheduled_at)->format('M j, Y'),
            'time_label' => optional($this->scheduled_at)->format('g:i A'),
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'student' => $this->whenLoaded('student', fn() => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'email' => $this->student->email,
                'phone' => $this->student->phone,
            ]),
        ];
    }
}
