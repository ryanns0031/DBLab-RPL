<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $guarded = ['id'];

    public function students()
    {
        return $this->hasMany(User::class, 'class_id')->where('role', 'siswa');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_classes', 'class_id', 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
