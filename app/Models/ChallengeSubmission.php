<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeSubmission extends Model
{
    use HasFactory;

    protected $table = 'challenge_submissions';

    protected $guarded = ['id'];

    protected $casts = ['started_at' => 'datetime', 'ends_at' => 'datetime', 'completed_at' => 'datetime'];
}
