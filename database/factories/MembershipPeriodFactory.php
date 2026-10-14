<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipPeriodFactory extends Factory
{
    public function definition(): array
    {
        throw new \LogicException('Create periods via MembershipService::settle.');
    }
}
