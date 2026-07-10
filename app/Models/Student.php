<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_code',
        'batch_id',
        'tutor_id',
        'name',
        'gender',
        'phone',
        'email',
        'photo',
        'status',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo('Batch::class');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}