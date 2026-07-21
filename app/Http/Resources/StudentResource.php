<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'student_code' => $this->student_code,
            'name' => $this->name,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'photo' => $this->photo ? Storage::url($this->photo) : null,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'batch' => $this->whenLoaded('batch', fn() => [
                'id' => $this->batch->id,
                'batch_name' => $this->batch->batch_name,
                'year' => $this->batch->year,
            ]),
            'tutor' => $this->whenLoaded('tutor', fn() => [
                'id' => $this->tutor->id,
                'name' => $this->tutor->name,
                'email' => $this->tutor->email,
            ]),
        ];
    }
}
