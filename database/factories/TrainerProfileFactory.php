<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerProfileFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory()->state(['role' => 'personal_trainer']), 'specialization' => 'Strength & Pilates', 'experience_years' => 3];
    }
}
