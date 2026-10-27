<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SqlShopkeeperProgress extends Model
{
    protected $table = 'sql_shopkeeper_progress';

    protected $guarded = ['id'];

    protected $casts = [
        'game_state' => 'array',
        'last_played_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
