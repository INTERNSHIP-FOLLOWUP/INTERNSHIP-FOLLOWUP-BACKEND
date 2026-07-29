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
        'work_date',
        'work_time',
        'work_activities',
        'what_learned',
        'difficulties',
        'solutions',
        'to_do',
        'comment',
        'status',
        'feedback',
        'reviewer_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'work_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
