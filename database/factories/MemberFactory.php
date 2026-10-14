<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    public function definition(): array
    {
        return ['full_name' => fake()->name(), 'member_number' => 'TEST-'.fake()->unique()->numerify('########'), 'phone' => '+628'.fake()->unique()->numerify('##########'), 'status' => 'active'];
    }
}
