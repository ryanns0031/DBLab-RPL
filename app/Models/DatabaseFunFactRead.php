<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseFunFactRead extends Model
{
    protected $fillable = [
        'student_id',
        'database_fun_fact_id',
        'first_read_at',
        'last_read_at',
    ];

    protected $casts = [
        'first_read_at' => 'datetime',
        'last_read_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function funFact(): BelongsTo
    {
        return $this->belongsTo(DatabaseFunFact::class, 'database_fun_fact_id');
    }
}

