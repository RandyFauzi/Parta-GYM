<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GymOperatingHourFactory extends Factory
{
    public function definition(): array
    {
        return ['id' => 1, 'closed' => false, 'opens_at' => '06:00', 'closes_at' => '22:00'];
    }
}
