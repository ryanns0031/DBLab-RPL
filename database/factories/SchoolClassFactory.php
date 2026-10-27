<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->bothify('XI RPL ??'), 'level' => 'XI', 'academic_year_id' => AcademicYear::factory(), 'status' => 'aktif'];
    }
}
