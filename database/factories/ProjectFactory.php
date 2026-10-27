<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Project latihan', 'description' => 'Buat database', 'week' => 10, 'deadline' => now()->addMonth(), 'class_id' => SchoolClass::factory(), 'teacher_id' => User::factory()->state(['role' => 'guru']), 'status' => 'aktif'];
    }
}
