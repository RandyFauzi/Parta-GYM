<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainerAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(), 'trainer_profile_id' => TrainerProfile::factory(),
            'activity' => 'gym', 'starts_at' => now()->subMinute(), 'ends_at' => null,
            'active_key' => fn (array $attributes): string => $attributes['member_id'].':'.$attributes['activity'],
            'assigned_by' => User::factory()->state(['role' => 'owner']),
            'reason' => 'Penugasan oleh management.',
        ];
    }
}
