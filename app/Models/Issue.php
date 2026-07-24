<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'Open';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_RESOLVED = 'Resolved';
    public const STATUS_CLOSED = 'Closed';

    public const PRIORITY_LOW = 'Low';
    public const PRIORITY_MEDIUM = 'Medium';
    public const PRIORITY_HIGH = 'High';
    public const PRIORITY_CRITICAL = 'Critical';

    protected $fillable = [
        'student_id',
        'reporter_id',
        'tutor_id',
        'assigned_user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'status' => 'string',
        'priority' => 'string',
        'due_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        // tutor_id references users.id, not tutors.id
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(IssueHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IssueAttachment::class);
    }

    /**
     * Check if issue can transition to the given status
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $currentStatus = $this->status;

        $allowedTransitions = [
            self::STATUS_OPEN => [self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED],
            self::STATUS_IN_PROGRESS => [self::STATUS_OPEN, self::STATUS_RESOLVED, self::STATUS_CLOSED],
            self::STATUS_RESOLVED => [self::STATUS_CLOSED, self::STATUS_OPEN],
            self::STATUS_CLOSED => [],
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }
}
