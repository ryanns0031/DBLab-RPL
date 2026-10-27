<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProjectStep extends Model
{
    use HasFactory;

    protected $table = 'student_project_steps';

    protected $guarded = ['id'];
}
