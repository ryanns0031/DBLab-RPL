<?php

namespace App\Services;

use App\Models\ActivityLog;

class Audit
{
    public static function log(string $message): void
    {
        ActivityLog::create(['user_id' => auth()->id(), 'description' => $message]);
    }
}
