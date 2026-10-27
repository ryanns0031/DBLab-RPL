<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    use HasFactory;

    protected $table = 'test_results';

    protected $guarded = ['id'];

    protected $casts = ['snapshot' => 'array', 'submitted_at' => 'datetime', 'started_at' => 'datetime', 'ends_at' => 'datetime'];

    public function test()
    {
        return $this->belongsTo(LearningTest::class, 'test_id');
    }
}
