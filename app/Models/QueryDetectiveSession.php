<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryDetectiveSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'case_order' => 'array',
        'case_progress' => 'array',
        'completed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
