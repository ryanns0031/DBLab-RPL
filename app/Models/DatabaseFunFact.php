<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseFunFact extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'summary',
        'content',
        'real_world_example',
        'database_concept',
        'sql_connection',
        'illustration_type',
        'source_reference',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(DatabaseFunFactRead::class, 'database_fun_fact_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if (! $category || $category === 'Semua') {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function isReadBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->reads()->where('student_id', $user->id)->exists();
    }
}

