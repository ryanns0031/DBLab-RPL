<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChallengeFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Latihan SELECT', 'description' => 'Tampilkan nama siswa', 'material_id' => Material::factory(), 'class_id' => SchoolClass::factory(), 'teacher_id' => User::factory()->state(['role' => 'guru']), 'difficulty' => 'Mudah', 'expected_query' => 'SELECT nama FROM siswa', 'expected_result' => [['nama' => 'Andi'], ['nama' => 'Rina'], ['nama' => 'Budi'], ['nama' => 'Dewi']], 'points' => 100, 'status' => 'aktif'];
    }
}
