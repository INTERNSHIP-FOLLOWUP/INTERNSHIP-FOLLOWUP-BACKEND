<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFeedback extends Model
{
    use HasFactory;

    protected $table = 'company_feedback';

    protected $fillable = [
        'company_supervisors_id',
        'student_id',
        'title',
        'message',
        'strengths',
        'improvement_areas',
    ];

    protected function casts(): array
    {
        return [
            'strengths' => 'array',
            'improvement_areas' => 'array',
        ];
    }

    public const STRENGTHS = [
        'Good Communication',
        'Strong Teamwork',
        'Quick Learner',
        'Responsible',
        'Punctual',
        'Good Problem-Solving',
        'Good Technical Skills',
        'Positive Attitude',
        'Takes Initiative',
        'Adapts Quickly',
        'Follows Instructions Well',
        'Professional Behavior',
    ];

    public const IMPROVEMENT_AREAS = [
        'Communication Skills',
        'Teamwork',
        'Technical Skills',
        'Time Management',
        'Problem-Solving',
        'Confidence',
        'Responsibility',
        'Attendance',
        'Work Quality',
        'Attention to Detail',
        'Initiative',
        'Adaptability',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisors_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
