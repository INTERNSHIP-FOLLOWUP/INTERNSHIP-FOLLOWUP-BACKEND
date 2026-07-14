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
<<<<<<< HEAD
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
        ];
    }
=======
    ];

    protected $casts = [
        'submission_date' => 'date',
    ];
>>>>>>> feature/evaluation-issue

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> feature/evaluation-issue
