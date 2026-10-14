<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        throw new \LogicException('Create payments via MembershipService::settle.');
    }
}
