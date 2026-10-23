<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberProgressRecordFactory extends Factory
{
    public function definition(): array
    {
        return ['member_id' => Member::factory(), 'trainer_id' => User::factory()->state(['role' => 'personal_trainer']), 'recorded_at' => now(), 'workout_summary' => 'Evaluasi latihan member.'];
    }
}
