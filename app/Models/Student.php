<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_code',
        'batch_id',
        'tutor_id',
        'first_name',
        'last_name',
        'gender',
        'phone',
        'email',
        'photo',
        'status',
    ];

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    protected $casts = [
        'batch_id' => 'integer',
        'tutor_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function worklogs(): HasMany
    {
        return $this->hasMany(Worklog::class);
    }

    public function internshipAssignment(): HasOne
    {
        return $this->hasOne(InternshipAssignment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
