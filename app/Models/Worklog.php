<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worklog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'week_number',
        'description',
        'challenges',
        'submission_date',
        'status',
        'feedback',
    ];

    protected $appends = [
        'submitted_at',
        'tutor_review',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function getSubmittedAtAttribute(): ?string
    {
        return $this->submission_date?->toIso8601String();
    }

    public function getTutorReviewAttribute(): ?array
    {
        if ($this->feedback === null || $this->feedback === '') {
            return null;
        }

        return [
            'tutor_name' => data_get($this, 'student.user.name'),
            'reviewed_at' => $this->updated_at?->toIso8601String(),
            'feedback' => $this->feedback,
            'status' => $this->status,
        ];
    }
}
