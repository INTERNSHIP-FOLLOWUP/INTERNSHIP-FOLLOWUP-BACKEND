<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\CompanyFeedback;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'address',
        'industry',
        'contact_person',
        'phone',
        'email',
        'password',
        'website',
        'company_profile_image',
        'company_image',
        'telegram_link',
        'role',
    ];

    protected $appends = ['company_image_url', 'company_profile_image_url'];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getCompanyImageUrlAttribute(): ?string
    {
        if (!$this->company_image) return null;

        if (str_starts_with($this->company_image, 'http://') ||
            str_starts_with($this->company_image, 'https://')) {
            return $this->company_image;
        }

        return Storage::url($this->company_image);
    }

    public function getCompanyProfileImageUrlAttribute(): ?string
    {
        if (!$this->company_profile_image) return null;

        // Already a URL — return as-is
        if (str_starts_with($this->company_profile_image, 'http://') ||
            str_starts_with($this->company_profile_image, 'https://')) {
            return $this->company_profile_image;
        }

        // File path — generate storage URL
        return Storage::url($this->company_profile_image);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function internshipAssignments(): HasMany
    {
        return $this->hasMany(InternshipAssignment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(CompanyFeedback::class);
    }
}
