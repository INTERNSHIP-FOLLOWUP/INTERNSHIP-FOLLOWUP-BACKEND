<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanySupervisor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
    ];

    protected $appends = ['name', 'status'];

    public function getNameAttribute(): string
    {
        return $this->user?->name ?? '';
    }

    public function getStatusAttribute(): ?string
    {
        return $this->user?->status;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
