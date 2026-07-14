<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'worklog_id',
        'file_path',
        'file_type',
        'file_size',
    ];

<<<<<<< HEAD
=======
    protected $casts = [
        'file_size' => 'integer',
    ];

>>>>>>> feature/evaluation-issue
    public function worklog(): BelongsTo
    {
        return $this->belongsTo(Worklog::class);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> feature/evaluation-issue
