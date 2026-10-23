<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Member;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function checkout(Checkin $visit, ?User $staff = null): bool
    {
        if ($staff) {
            abort_unless($staff->is_active && ! $staff->trashed() && in_array($staff->role, ['owner', 'receptionist'], true), 403);
        }

        return DB::transaction(function () use ($visit, $staff): bool {
            Member::whereKey($visit->member_id)->lockForUpdate()->firstOrFail();
            $visit = Checkin::whereKey($visit->id)->lockForUpdate()->firstOrFail();
            if ($visit->checked_out_at) {
                return false;
            }
            $automatic = $visit->checkout_due_at && $visit->checkout_due_at->lte(now());
            if (! $staff && ! $automatic) {
                return false;
            }
            $visit->update([
                'checked_out_at' => $automatic ? $visit->checkout_due_at : CarbonImmutable::now(),
                'checkout_method' => $automatic ? 'automatic' : 'manual',
                'checked_out_by' => $automatic ? null : $staff->id,
            ]);
            app(MembershipService::class)->audit('checkin.checked_out', $visit->id, [
                'method' => $visit->checkout_method, 'checked_out_at' => $visit->checked_out_at->toIso8601String(),
            ]);

            return true;
        }, 3);
    }

    public function closeDue(): int
    {
        $count = 0;
        Checkin::whereNull('checked_out_at')->where('checkout_due_at', '<=', now())->eachById(function (Checkin $visit) use (&$count): void {
            if ($this->checkout($visit)) {
                $count++;
            }
        });

        return $count;
    }
}
