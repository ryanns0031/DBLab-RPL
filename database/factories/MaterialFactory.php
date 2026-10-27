<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'description' => fake()->sentence(), 'content' => fake()->paragraph(), 'week' => 1, 'sort_order' => 1, 'class_id' => SchoolClass::factory(), 'teacher_id' => User::factory()->state(['role' => 'guru']), 'status' => 'aktif'];
    }
}
