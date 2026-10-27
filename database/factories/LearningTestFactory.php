<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningTestFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Tes latihan', 'type' => 'pretest', 'class_id' => SchoolClass::factory(), 'teacher_id' => User::factory()->state(['role' => 'guru']), 'status' => 'aktif'];
    }
}
