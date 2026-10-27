<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    protected $guarded = ['id'];

    protected $casts = ['deadline' => 'date', 'deadline_at' => 'datetime', 'allow_late_submissions' => 'boolean'];

    public function steps()
    {
        return $this->hasMany(ProjectStep::class)->orderBy('sort_order');
    }
}
