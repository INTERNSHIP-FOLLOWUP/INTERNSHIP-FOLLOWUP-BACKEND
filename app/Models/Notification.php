<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'title',
        'message',
        'type',
        'priority',
        'entity_type',
        'entity_id',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('receiver_id', $userId);
    }

    public function scopeNewestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at');
    }
}