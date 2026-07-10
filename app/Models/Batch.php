<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'batch_name',
        'year',
    ];
    
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
