<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffShiftFactory extends Factory
{
    public function definition(): array
    {
        return ['staff_id' => User::factory()->state(['role' => 'receptionist']), 'opening_cash_idr' => 100000];
    }
}
