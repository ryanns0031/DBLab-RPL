<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryGuessSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'question_order' => 'array',
        'answers' => 'array',
        'completed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
