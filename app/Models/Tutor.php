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
        'first_name',
        'last_name',
        'gender',
        'phone',
        'email',
        'photo',
        'status',
    ];

    protected $appends = ['name'];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function students(): HasMany
    {
        // tutor_id references users.id, so use user_id as local key
        return $this->hasMany(Student::class, 'tutor_id', 'user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InternshipAssignment::class, 'tutor_id', 'user_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'tutor_id', 'user_id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class, 'tutor_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CompanyMessage::class, 'tutor_id', 'user_id');
    }
}
