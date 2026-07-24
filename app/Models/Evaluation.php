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
        'company_supervisors_id',
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

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisors_id');
    }
}
