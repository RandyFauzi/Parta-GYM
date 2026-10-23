<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipPeriod;
use App\Services\PackageBenefits;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipEntitlementSeeder extends Seeder
{
    public function run(): void
    {
        MembershipPeriod::whereDoesntHave('entitlements')->eachById(function (MembershipPeriod $period): void {
            DB::transaction(function () use ($period): void {
                Member::whereKey($period->member_id)->lockForUpdate()->firstOrFail();
                app(PackageBenefits::class)->initialize($period, $period->invoice->snapshot['benefits'] ?? PackageBenefits::legacy($period->plan_type, $period->visit_limit));
            });
        });
    }
}
