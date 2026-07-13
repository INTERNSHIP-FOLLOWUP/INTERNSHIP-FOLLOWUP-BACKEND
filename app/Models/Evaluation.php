<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'company_id',
        'technical_skill',
        'communication',
        'professionalism',
        'attendance',
        'overall_score',
        'feedback',
    ];

    protected $casts = [
        'technical_skill' => 'integer',
        'communication' => 'integer',
        'professionalism' => 'integer',
        'attendance' => 'integer',
        'overall_score' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Boot method to calculate overall_score before saving
     */
    protected static function booted()
    {
        static::saving(function (Evaluation $evaluation) {
            $evaluation->overall_score = (int) round(
                ($evaluation->technical_skill +
                $evaluation->communication +
                $evaluation->professionalism +
                $evaluation->attendance) / 4
            );
        });
    }
}
