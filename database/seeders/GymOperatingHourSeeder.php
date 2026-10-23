<?php

namespace Database\Seeders;

use App\Models\GymOperatingHour;
use Illuminate\Database\Seeder;

class GymOperatingHourSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(0, 6) as $day) {
            GymOperatingHour::firstOrCreate(['id' => $day], [
                'closed' => false, 'opens_at' => $day === 0 ? '08:00' : '06:00', 'closes_at' => $day === 0 ? '20:00' : ($day === 6 ? '21:00' : '22:00'),
            ]);
        }
    }
}
