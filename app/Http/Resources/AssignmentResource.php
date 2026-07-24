<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'company_supervisors_id' => $this->company_supervisors_id,
            'tutor_id' => $this->tutor_id,
            'position' => $this->position,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'student' => $this->whenLoaded('student', fn() => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'email' => $this->student->email,
            ]),
            'company' => $this->whenLoaded('supervisor', fn() => $this->supervisor->company ? [
                'id' => $this->supervisor->company->id,
                'company_name' => $this->supervisor->company->company_name,
                'company_email' => $this->supervisor->company->company_email,
            ] : null),
            'tutor' => $this->whenLoaded('tutor', fn() => [
                'id' => $this->tutor->id,
                'name' => $this->tutor->name,
                'email' => $this->tutor->email,
            ]),

            'student_name' => $this->whenLoaded('student', fn() => $this->student->name),
            'company_name' => $this->whenLoaded('supervisor', fn() => $this->supervisor->company?->company_name),
            'tutor_name' => $this->whenLoaded('tutor', fn() => $this->tutor->name),
        ];
    }
}
