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

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seed only permitted in local/testing.');
        }
        if (User::where('email', 'owner@gym.test')->exists()) {
            $this->command?->warn('Demo already exists; no changes made.');

            return;
        }
        $password = Str::password(20);
        DB::transaction(function () use ($password) {
            foreach (['owner' => 'Demo Owner', 'receptionist' => 'Demo Front Desk', 'member' => 'Demo Member'] as $role => $name) {
                $user = new User;
                $user->name = $name;
                $user->email = $role.'@gym.test';
                $user->password = Hash::make($password);
                $user->role = $role;
                $user->save();
            }
            $plans = collect();
            foreach ([['Daily Pass', 'day', 1, 50000, 1], ['Monthly Essential', 'month', 1, 300000, null], ['Quarterly Progress', 'month', 3, 800000, null], ['Annual Commitment', 'year', 1, 2800000, null]] as [$name,$unit,$value,$price,$limit]) {
                $plans->push(MembershipPlan::create(['name' => $name, 'duration_unit' => $unit, 'duration_value' => $value, 'price_idr' => $price, 'visit_limit' => $limit, 'description' => 'Paket contoh untuk lingkungan demo.']));
            }
            $service = app(MembershipService::class);
            $staff = User::where('role', 'owner')->firstOrFail();
            foreach (['Demo Member', 'Demo Nadia Putri', 'Demo Arif Pratama', 'Demo Rina Sari', 'Demo Dimas Saputra', 'Demo Maya Lestari'] as $i => $name) {
                $member = Member::create(['full_name' => $name, 'member_number' => 'DEMO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'phone' => '+62812345000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'user_id' => $i === 0 ? User::where('role', 'member')->value('id') : null]);
                $invoice = $service->createInvoice($member, $plans[$i % 4], (string) Str::uuid());
                if ($i < 5) {
                    $service->settle($invoice, $staff, 'bank_transfer', 'DEMO-TRANSFER-'.$i);
                    if ($i < 3) {
                        $service->checkin($member, $staff);
                    }
                }
            }
        });
        $this->command?->info('Demo accounts: owner@gym.test / receptionist@gym.test / member@gym.test');
        $this->command?->line('Generated local demo password: '.$password);
    }
}
