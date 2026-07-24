<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tutor_code',
    ];

    protected $appends = ['name', 'first_name', 'last_name', 'email', 'phone', 'photo_url', 'gender', 'status'];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    public function getFirstNameAttribute(): ?string
    {
        return $this->user?->first_name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->user?->last_name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->user?->avatar_url;
    }

    public function getGenderAttribute(): ?string
    {
        return $this->user?->gender;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->user?->status;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'tutor_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InternshipAssignment::class, 'tutor_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'tutor_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class, 'tutor_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CompanyMessage::class, 'tutor_id');
    }
}
