<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discussion extends Model
{
    protected $fillable = [
        'user_id',
        'discussion_category_id',
        'title',
        'slug',
        'content',
        'sql_snippet',
        'status',
        'solved_reply_id',
        'views_count',
        'replies_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DiscussionCategory::class, 'discussion_category_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(DiscussionReply::class);
    }

    public function solvedReply(): BelongsTo
    {
        return $this->belongsTo(DiscussionReply::class, 'solved_reply_id');
    }

    public function isSolved(): bool
    {
        return $this->status === 'terjawab' || ! is_null($this->solved_reply_id);
    }

    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        if ($status === 'terjawab') {
            return $query->where('status', 'terjawab');
        }

        if ($status === 'belum_terjawab') {
            return $query->where('status', 'belum_terjawab');
        }

        return $query;
    }
}

