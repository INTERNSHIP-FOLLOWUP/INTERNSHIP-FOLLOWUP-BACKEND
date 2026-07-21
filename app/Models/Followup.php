<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'company_id',
        'meeting_type',
        'meeting_date',
        'notes',
        'action_items',
        'next_followup',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date:Y-m-d',
            'next_followup' => 'date:Y-m-d',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }
}
