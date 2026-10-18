<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Monthly Test',
            'plan_type' => 'gym',
            'duration_unit' => 'month',
            'duration_value' => 1,
            'price_idr' => 300000,
            'visit_limit' => null,
            'is_active' => true,
        ];
    }
}
