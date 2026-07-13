<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'address',
        'industry',
        'contact_person',
        'phone',
        'email',
        'website',
        'company_profile_image',
        'telegram_link',
    ];

    public function internshipAssignments(): HasMany
    {
        return $this->hasMany(InternshipAssignment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
