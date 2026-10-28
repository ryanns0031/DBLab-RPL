<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProjectShowcase extends Model
{
    public const CATEGORIES = [
        'Sistem Informasi Sekolah',
        'Perpustakaan',
        'Inventaris',
        'Toko Online',
        'Keuangan',
        'Sistem Absensi',
        'Project SQL',
        'Lainnya',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'menunggu_review';
    public const STATUS_APPROVED = 'disetujui';
    public const STATUS_REVISION = 'perlu_revisi';
    public const STATUS_REJECTED = 'ditolak';

    protected $fillable = [
        'student_id',
        'project_id',
        'title',
        'slug',
        'category',
        'short_description',
        'description',
        'purpose',
        'problem_solved',
        'features',
        'thumbnail_path',
        'erd_path',
        'database_structure',
        'sql_examples',
        'challenges_and_solutions',
        'lessons_learned',
        'future_improvements',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'published_at',
        'likes_count',
        'views_count',
    ];

    protected $casts = [
        'database_structure' => 'array',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ProjectShowcaseLike::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ProjectShowcaseFavorite::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        // Return Supabase public URL if configured
        return Storage::disk('supabase_public')->url($this->thumbnail_path);
    }

    public function getErdUrlAttribute(): ?string
    {
        if (! $this->erd_path) {
            return null;
        }

        return Storage::disk('supabase_public')->url($this->erd_path);
    }
}

