<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipChangeFactory extends Factory
{
    public function definition(): array
    {
        throw new \LogicException('Buat perubahan melalui MembershipChangeService agar transaksi dan kredit konsisten.');
    }
}
