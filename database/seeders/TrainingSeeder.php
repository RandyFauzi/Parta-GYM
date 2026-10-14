<?php

namespace Database\Seeders;

use App\Models\StudioRoom;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        StudioRoom::firstOrCreate(['name' => 'Studio Pilates'], ['capacity' => 8, 'is_exclusive' => true, 'is_active' => true]);
        StudioRoom::firstOrCreate(['name' => 'Gym Floor'], ['capacity' => 20, 'is_exclusive' => false, 'is_active' => true]);
    }
}
