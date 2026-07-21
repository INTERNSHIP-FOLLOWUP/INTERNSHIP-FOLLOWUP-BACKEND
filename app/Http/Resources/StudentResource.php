<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_code' => $this->student_code,
            'name' => $this->name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'photo' => $this->photo,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'batch' => $this->whenLoaded('batch', fn() => [
                'id' => $this->batch->id,
                'batch_name' => $this->batch->batch_name,
                'name' => $this->batch->batch_name,
                'year' => $this->batch->year,
            ]),
            'tutor' => $this->whenLoaded('tutor', fn() => [
                'id' => $this->tutor->id,
                'name' => $this->tutor->name,
                'email' => $this->tutor->email,
            ]),
        ];

        // Extra fields used by tutor student list/detail views
        if ($this->relationLoaded('assignment_status')) {
            $data['assignment_status'] = $this->assignment_status;
        }
        if ($this->relationLoaded('company_name')) {
            $data['company_name'] = $this->company_name;
        }
        if ($this->relationLoaded('position')) {
            $data['position'] = $this->position;
        }
        if ($this->relationLoaded('last_worklog_at')) {
            $data['last_worklog_at'] = $this->last_worklog_at;
        }
        if ($this->relationLoaded('feedback_given')) {
            $data['feedback_given'] = $this->feedback_given;
        }
        if ($this->relationLoaded('open_issues_count')) {
            $data['open_issues_count'] = $this->open_issues_count;
        }
        if ($this->relationLoaded('next_followup')) {
            $data['next_followup'] = $this->next_followup;
        }

        // Worklogs, issues, evaluations for detail view
        if ($this->relationLoaded('worklogs')) {
            $data['worklogs'] = $this->worklogs->map(fn($w) => [
                'id' => $w->id,
                'week_number' => $w->week_number,
                'status' => $w->status,
                'submitted_at' => optional($w->created_at)->toISOString(),
                'created_at' => optional($w->created_at)->toISOString(),
                'description' => $w->description,
                'challenges' => $w->challenges,
            ])->values()->all();
        }
        if ($this->relationLoaded('issues')) {
            $data['issues'] = $this->issues->map(fn($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'status' => $i->status,
                'priority' => $i->priority,
                'created_at' => optional($i->created_at)->toISOString(),
            ])->values()->all();
        }
        if ($this->relationLoaded('evaluations')) {
            $data['evaluations'] = $this->evaluations->map(fn($e) => [
                'id' => $e->id,
                'rating' => $e->overall_score,
                'remarks' => $e->feedback,
            ])->values()->all();
        }

        return $data;
    }
}

