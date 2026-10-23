<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\TrainerAssignmentService;
use Illuminate\Database\Seeder;

class TrainerAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! config('gym.preview_seed_enabled')) {
            throw new \RuntimeException('Penugasan contoh hanya tersedia pada preview lokal yang diaktifkan.');
        }
        $owner = User::where('role', 'owner')->where('is_active', true)->firstOrFail();
        $trainer = TrainerProfile::whereHas('user', fn ($query) => $query->where('role', 'personal_trainer')->where('is_active', true))->firstOrFail();
        $member = Member::where('status', 'active')->whereDoesntHave('trainerAssignments')->firstOrFail();
        app(TrainerAssignmentService::class)->assign($member, $trainer, 'gym', null, 'Penugasan contoh oleh management preview.', $owner);
    }
}
