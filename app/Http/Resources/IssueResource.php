<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
    $attachmentsCount = $this->whenLoaded('attachments', fn() => $this->attachments->count(), 0);
        $attachmentList = $this->whenLoaded('attachments', fn() =>
            $this->attachments->map(fn($a) => [
                'id' => $a->id,
                'filename' => $a->filename,
                'file_path' => $a->file_path ? url('storage/' . $a->file_path) : null,
                'file_type' => $a->file_type,
                'file_size' => $a->file_size,
            ])->values()->all()
        );
        $history = $this->whenLoaded('history', fn() =>
            $this->history->map(fn($h) => [
                'time' => $h->created_at?->toISOString(),
                'user' => $h->user?->name ?? 'Unknown',
                'text' => $h->text,
            ])->values()->all()
        );

        return [
            'id' => 'ISSUE-' . str_pad($this->id, 3, '0', STR_PAD_LEFT),
            'title' => $this->title,
            'description' => $this->description,
            'reporter' => $this->reporter?->name ?? $this->student?->name ?? 'Unknown',
            'studentName' => $this->student?->name ?? '',
            'assignedTo' => $this->assignedUser?->name ?? $this->tutor?->name ?? 'Unassigned',
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'status' => $this->status,
            'priority' => $this->priority,
            'attachments' => $attachmentsCount,
            'attachmentList' => $attachmentList ?? [],
            'studentId' => $this->student_id,
            'assignedUserId' => $this->assigned_user_id,
            'due_date' => $this->due_date?->toDateString(),
            'history' => $history ?? [],
        ];
    }
}

