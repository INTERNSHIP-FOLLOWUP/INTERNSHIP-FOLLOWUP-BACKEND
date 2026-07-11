<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Tutor extends Authenticatable
{
    /** @use HasFactory<TutorFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
    ];

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

    /**
     * Scope a query to only include tutors.
     */
    public function scopeTutors($query)
    {
        return $query->where('role', 'tutor')->whereNull('deleted_at');
    }

    /**
     * Get the role that belongs to the tutor.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}