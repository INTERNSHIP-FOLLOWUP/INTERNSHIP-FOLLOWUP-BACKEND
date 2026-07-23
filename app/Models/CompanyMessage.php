<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMessage extends Model
{
    protected $fillable = [
        'company_id',
        'tutor_id',
        'sender_type',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tutor(): BelongsTo
    {
        // tutor_id references users.id, not tutors.id
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
