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
    ];

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->user?->avatar_url;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function getFirstNameAttribute(): ?string
    {
        return $this->user?->first_name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->user?->last_name;
    }

    public function getGenderAttribute(): ?string
    {
        return $this->user?->gender;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->user?->status;
    }

    public function getCompanyNameAttribute(): ?string
    {
        return $this->internshipAssignment?->supervisor?->company?->company_name;
    }

    public function getPositionAttribute(): ?string
    {
        return $this->internshipAssignment?->position;
    }

    protected $hidden = ['user'];

    protected $appends = ['name', 'first_name', 'last_name', 'email', 'phone', 'photo_url', 'gender', 'status', 'company_name', 'position'];

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
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function tutorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id', 'id');
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
        return $this->hasOne(InternshipAssignment::class)->latestOfMany();
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
