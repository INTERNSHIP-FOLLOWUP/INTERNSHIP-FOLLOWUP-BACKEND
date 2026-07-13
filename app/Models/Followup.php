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
            'meeting_date' => 'date',
            'next_followup' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}