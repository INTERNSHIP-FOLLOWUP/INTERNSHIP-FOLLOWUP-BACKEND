<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipAssignment extends Model
{
    protected $fillable = [
        'student_id',
        'company_id',
        'tutor_id',
        'position',
        'start_date',
        'end_date',
        'status',
    ];

    protected $appends = [
        'student_name',
        'company_name',
        'tutor_name',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function getStudentNameAttribute(): string
    {
        return $this->student?->name ?? '';
    }

    public function getCompanyNameAttribute(): string
    {
        return $this->company?->company_name ?? '';
    }

    public function getTutorNameAttribute(): string
    {
        return $this->tutor?->name ?? '';
    }
}