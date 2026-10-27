<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->numerify('20##/20##'), 'semester' => 'Ganjil', 'active' => false];
    }
}
