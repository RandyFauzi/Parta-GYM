<?php

namespace Database\Factories;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerTimeOffFactory extends Factory
{
    public function definition(): array
    {
        return ['trainer_profile_id' => TrainerProfile::factory(), 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(2), 'reason' => 'Cuti trainer oleh management', 'created_by' => User::factory()->state(['role' => 'owner'])];
    }
}
