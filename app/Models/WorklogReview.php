<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorklogReview extends Model
{
    protected $fillable = [
        'worklog_id',
        'tutor_id',
        'feedback',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function worklog(): BelongsTo
    {
        return $this->belongsTo(Worklog::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
