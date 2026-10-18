<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use App\Models\StudioRoom;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        StudioRoom::firstOrCreate(['name' => 'Studio Pilates'], ['capacity' => 8, 'is_exclusive' => true, 'is_active' => true]);
        StudioRoom::firstOrCreate(['name' => 'Gym Floor'], ['capacity' => 20, 'is_exclusive' => false, 'is_active' => true]);

        // Paket Personal Trainer (PT)
        MembershipPlan::firstOrCreate(['name' => 'PT Full 30 Hari'], [
            'plan_type' => 'pt',
            'duration_unit' => 'day',
            'duration_value' => 30,
            'price_idr' => 700000,
            'visit_limit' => null,
            'description' => 'Paket pendampingan Personal Trainer full 30 hari tanpa batas sesi.',
            'is_active' => true,
        ]);

        MembershipPlan::firstOrCreate(['name' => 'PT 15 Sesi'], [
            'plan_type' => 'pt',
            'duration_unit' => 'day',
            'duration_value' => 60,
            'price_idr' => 500000,
            'visit_limit' => 15,
            'description' => 'Paket pendampingan Personal Trainer 15 sesi latihan (masa aktif 60 hari).',
            'is_active' => true,
        ]);

        MembershipPlan::firstOrCreate(['name' => 'PT 10 Sesi'], [
            'plan_type' => 'pt',
            'duration_unit' => 'day',
            'duration_value' => 30,
            'price_idr' => 250000,
            'visit_limit' => 10,
            'description' => 'Paket pendampingan Personal Trainer 10 sesi latihan (masa aktif 30 hari).',
            'is_active' => true,
        ]);
    }
}
