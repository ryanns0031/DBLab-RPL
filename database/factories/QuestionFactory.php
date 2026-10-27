<?php

namespace Database\Factories;

use App\Models\LearningTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return ['test_id' => LearningTest::factory(), 'question' => 'Perintah mengambil data?', 'option_a' => 'SELECT', 'option_b' => 'DROP', 'option_c' => 'ALTER', 'option_d' => 'DELETE', 'correct_answer' => 'A'];
    }
}
