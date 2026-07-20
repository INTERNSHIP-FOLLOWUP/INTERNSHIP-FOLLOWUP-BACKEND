<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFeedback extends Model
{
    use HasFactory;

    protected $table = 'company_feedback';

    protected $fillable = [
        'company_id',
        'title',
        'message',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
