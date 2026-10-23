<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipEntitlementFactory extends Factory
{
    public function definition(): array
    {
        return ['benefit_key' => 'gym_access', 'quota' => 10];
    }
}
