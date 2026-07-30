<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Evaluation;
use App\Models\Followup;
use App\Models\InternshipAssignment;
use App\Models\Issue;
use App\Models\Student;
use App\Models\Tutor;
use App\Models\Worklog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sender_id',
        'sender_type',
        'event',
        'category',
        'title',
        'message',
        'reference_type',
        'reference_id',
        'action_url',
        'priority',
        'icon',
        'color',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // Scopes
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('category', 'general');
    }

    public function scopeEvaluation(Builder $query): Builder
    {
        return $query->where('category', 'evaluation');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender()
    {
        switch ($this->sender_type) {
            case 'company':
                return $this->belongsTo(Company::class, 'sender_id');
            case 'tutor':
                return $this->belongsTo(Tutor::class, 'sender_id');
            case 'student':
                return $this->belongsTo(Student::class, 'sender_id');
            default:
                return null;
        }
    }

    public function reference()
    {
        switch ($this->reference_type) {
            case 'worklog':
                return $this->belongsTo(Worklog::class, 'reference_id');
            case 'assignment':
                return $this->belongsTo(InternshipAssignment::class, 'reference_id');
            case 'issue':
                return $this->belongsTo(Issue::class, 'reference_id');
            case 'evaluation':
                return $this->belongsTo(Evaluation::class, 'reference_id');
            case 'followup':
                return $this->belongsTo(Followup::class, 'reference_id');
            default:
                return null;
        }
    }

    // Methods
    public function markAsRead(): self
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this;
    }

    public function markAsUnread(): self
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return $this;
    }
}