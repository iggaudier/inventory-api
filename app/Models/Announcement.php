<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'media_path',
        'media_type',
        'created_by',
    ];

    protected $appends = ['media_url'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? asset('storage/'.$this->media_path) : null;
    }
}
