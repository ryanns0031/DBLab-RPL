<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectShowcaseFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'project_showcase_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showcase(): BelongsTo
    {
        return $this->belongsTo(ProjectShowcase::class, 'project_showcase_id');
    }
}

