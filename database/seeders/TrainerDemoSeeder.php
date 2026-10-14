<?php

namespace Database\Seeders;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainerDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('gym.preview_seed_password');
        if (! config('gym.preview_seed_enabled') || ! is_string($password) || strlen($password) < 8) {
            throw new \RuntimeException('Enable PREVIEW_SEED_ENABLED and supply PREVIEW_SEED_PASSWORD (minimum 8 characters).');
        }
        DB::transaction(function () use ($password) {
            foreach ([['Demo Arga Pratama', 'Strength & conditioning', 6], ['Demo Nadia Putri', 'Pilates & core', 5], ['Demo Dimas Saputra', 'Mobility & functional training', 4]] as $index => [$name, $specialization, $years]) {
                $user = User::withTrashed()->firstOrNew(['email' => 'trainer'.($index + 1).'@sparta.test']);
                if ($user->exists && ($user->role !== 'personal_trainer' || $user->trashed())) {
                    throw new \RuntimeException('Demo email is reserved by another or archived account.');
                }
                if (! $user->exists) {
                    $user->forceFill(['name' => $name, 'role' => 'personal_trainer', 'password' => $password, 'is_active' => true])->save();
                }
                TrainerProfile::firstOrCreate(['user_id' => $user->id], ['specialization' => $specialization, 'experience_years' => $years, 'bio' => 'Profil contoh untuk menguji jadwal kelas dan personal training.', 'qualifications' => 'Data sertifikasi contoh untuk pengujian.']);
            }
        });
    }
}
