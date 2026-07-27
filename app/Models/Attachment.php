<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'worklog_id',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $appends = [
        'filename',
        'url',
        'mime_type',
        'size_bytes',
    ];

    protected $hidden = [
        'file_path',
        'file_type',
        'file_size',
    ];

    public function worklog(): BelongsTo
    {
        return $this->belongsTo(Worklog::class);
    }

    protected function filename(): Attribute
    {
        return Attribute::get(fn() => basename($this->file_path));
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn() => Storage::disk('public')->url($this->file_path));
    }

    protected function mimeType(): Attribute
    {
        return Attribute::get(fn() => $this->file_type);
    }

    protected function sizeBytes(): Attribute
    {
        return Attribute::get(fn() => $this->file_size);
    }
}
