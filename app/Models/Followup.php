<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'type',
        'scheduled_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
