<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudioRoomFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Studio '.fake()->unique()->numerify('####'), 'capacity' => 10, 'is_exclusive' => true, 'is_active' => true];
    }
}
