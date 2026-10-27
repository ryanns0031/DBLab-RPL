<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'teacher_classes', 'teacher_id', 'class_id');
    }

    public function results()
    {
        return $this->hasMany(TestResult::class, 'student_id');
    }

    public function funFactReads()
    {
        return $this->hasMany(DatabaseFunFactRead::class, 'student_id');
    }

    public function readFunFacts()
    {
        return $this->belongsToMany(DatabaseFunFact::class, 'database_fun_fact_reads', 'student_id', 'database_fun_fact_id')->withTimestamps();
    }
}
