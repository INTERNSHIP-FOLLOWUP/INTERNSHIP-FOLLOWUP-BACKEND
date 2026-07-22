<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
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

    public function canTransitionTo(string $newStatus): bool
    {
        $current = AssignmentStatus::tryFrom($this->status);

        if ($current === null) {
            return false;
        }

        $target = AssignmentStatus::tryFrom($newStatus);

        if ($target === null) {
            return false;
        }

        return $current->canTransitionTo($target);
    }
}