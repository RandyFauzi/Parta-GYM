<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        throw new \LogicException('Create invoices via MembershipService to preserve subscription and price snapshot invariants.');
    }
}
