<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    protected $table = 'challenges';

    protected $guarded = ['id'];

    protected $casts = ['expected_result' => 'array', 'duration_minutes' => 'integer'];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
