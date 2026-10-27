<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningTest extends Model
{
    use HasFactory;

    protected $table = 'tests';

    protected $guarded = ['id'];

    protected $casts = ['duration_minutes' => 'integer'];

    public function questions()
    {
        return $this->hasMany(Question::class, 'test_id');
    }
}
