<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ServerPreviewSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('gym.preview_seed_password');
        if (! config('gym.preview_seed_enabled') || ! is_string($password) || strlen($password) < 8) {
            throw new RuntimeException('Enable PREVIEW_SEED_ENABLED and supply PREVIEW_SEED_PASSWORD (minimum 8 characters).');
        }
        DB::transaction(function () use ($password) {
            $owner = $this->account('randyfauzi24@gmail.com', 'Randy Fauzi', 'owner', $password);
            $user = $this->account('member1@gmail.com', 'Member 1', 'member', $password);
            $plans = collect();
            foreach ([['Daily Pass', 'day', 1, 50000, 1], ['Monthly Essential', 'month', 1, 300000, null], ['Quarterly Progress', 'month', 3, 800000, null], ['Annual Commitment', 'year', 1, 2800000, null]] as [$name, $unit, $duration, $price, $limit]) {
                $plans->push(MembershipPlan::firstOrCreate(['name' => $name], ['duration_unit' => $unit, 'duration_value' => $duration, 'price_idr' => $price, 'visit_limit' => $limit, 'description' => 'Paket contoh untuk preview.']));
            }
            $service = app(MembershipService::class);
            foreach (['Member 1', 'Demo Nadia Putri', 'Demo Arif Pratama', 'Demo Rina Sari', 'Demo Dimas Saputra', 'Demo Maya Lestari'] as $index => $name) {
                $member = $index === 0 ? Member::where('user_id', $user->id)->first() : null;
                $member ??= Member::firstOrCreate(['member_number' => 'PREVIEW-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)], ['user_id' => $index === 0 ? $user->id : null, 'full_name' => $name, 'email' => $index === 0 ? $user->email : null, 'phone' => '+62800009900'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'status' => 'active']);
                if ($index === 0 && $member->user_id !== $user->id) {
                    throw new RuntimeException('Preview member number belongs to another account.');
                }
                if ($member->invoices()->exists()) {
                    continue;
                }
                $invoice = $service->createInvoice($member, $plans[$index === 0 ? 1 : $index % 4], (string) Str::uuid());
                if ($index < 5) {
                    $service->settle($invoice, $owner, 'bank_transfer', 'PREVIEW-TRANSFER-'.$index);
                    if ($index < 3) {
                        $service->checkin($member, $owner);
                    }
                }
            }
        });
        $this->command?->info('Preview accounts and sample memberships ready; existing accounts and records preserved.');
    }

    private function account(string $email, string $name, string $role, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        if ($user->exists) {
            if ($user->role !== $role) {
                throw new RuntimeException('Existing account has a different role: '.$email);
            }

            return $user;
        }
        $user->forceFill(['name' => $name, 'role' => $role, 'password' => Hash::make($password), 'is_active' => true])->save();

        return $user;
    }
}
