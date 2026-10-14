<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return ['training_session_id' => TrainingSession::factory(), 'member_id' => Member::factory(), 'created_by' => User::factory()->state(['role' => 'receptionist']), 'status' => 'confirmed'];
    }
}
