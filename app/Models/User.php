<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
    ];

    protected $appends = ['avatar_url'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) return null;

        if (str_starts_with($this->avatar, 'http://') ||
            str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return Storage::url($this->avatar);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tutorStudents(): HasMany
    {
        return $this->hasMany(Student::class, 'tutor_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function tutoredAssignments(): HasMany
    {
        return $this->hasMany(InternshipAssignment::class, 'tutor_id');
    }

    public function assignedIssues(): HasMany
    {
        return $this->hasMany(Issue::class, 'tutor_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\PasswordResetNotification($token));
    }
}
