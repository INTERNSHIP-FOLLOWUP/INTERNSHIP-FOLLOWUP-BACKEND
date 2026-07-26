<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'company_supervisors_id',
        'type',
        'scheduled_at',
        'notes',
        'action_items',
        'next_followup',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'next_followup' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisors_id');
    }

    public function companySupervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisors_id');
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Company::class,
            CompanySupervisor::class,
            'id',
            'id',
            'company_supervisors_id',
            'company_id'
        );
    }
}

