<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role_id',
        'avatar',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
        'name',
        'student_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
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

    public function getStudentCodeAttribute(): ?string
    {
        return $this->studentProfile?->student_code;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tutorProfile(): HasOne
    {
        return $this->hasOne(Tutor::class, 'user_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\PasswordResetNotification($token));
    }

    public function scopeWithTutorStudentCount(Builder $query): void
    {
        $query->select('*')->addSelect(DB::raw(
            '(SELECT COUNT(*) FROM students WHERE tutor_id IN (SELECT id FROM tutors WHERE user_id = users.id)) as students_count'
        ));
    }

    public function sentNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function receivedNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'receiver_id');
    }
}
