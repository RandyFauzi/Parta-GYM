<?php

namespace Database\Factories;

use App\Models\StudioRoom;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingSessionFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Pilates Foundation', 'activity' => 'pilates', 'kind' => 'group', 'trainer_profile_id' => TrainerProfile::factory(), 'studio_room_id' => StudioRoom::factory(), 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'capacity' => 6, 'status' => 'scheduled', 'created_by' => User::factory()->state(['role' => 'owner'])];
    }
}
