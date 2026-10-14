<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\StaffShift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function audit(string $action, string $id, array $metadata = []): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => auth()->id(), 'action' => $action, 'entity_id' => $id, 'metadata' => json_encode($metadata), 'created_at' => now()]);
    }

    public function createInvoice(Member $member, MembershipPlan $plan, string $key): Invoice
    {
        return DB::transaction(function () use ($member, $plan, $key) {
            Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            if ($existing = Invoice::where('request_key', $key)->first()) {
                abort_unless($existing->member_id === $member->id, 409);

                return $existing;
            }
            $plan = MembershipPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            if (! $plan->is_active) {
                throw ValidationException::withMessages(['plan_id' => 'Paket ini tidak tersedia.']);
            }
            $subscription = DB::table('subscriptions')->where('member_id', $member->id)->where('plan_id', $plan->id)->first();
            $subscriptionId = $subscription?->id ?? (string) Str::uuid();
            if (! $subscription) {
                DB::table('subscriptions')->insert(['id' => $subscriptionId, 'member_id' => $member->id, 'plan_id' => $plan->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            }
            $invoice = Invoice::create(['invoice_number' => 'INV-'.now()->format('ymd').'-'.strtoupper(Str::random(8)), 'member_id' => $member->id, 'subscription_id' => $subscriptionId, 'total_idr' => $plan->price_idr, 'snapshot' => ['name' => $plan->name, 'duration_unit' => $plan->duration_unit, 'duration_value' => $plan->duration_value, 'visit_limit' => $plan->visit_limit, 'price_idr' => $plan->price_idr], 'request_key' => $key]);
            $this->audit('invoice.created', $invoice->id);

            return $invoice;
        }, 3);
    }

    public function periodEnd(CarbonImmutable $start, string $unit, int $value): CarbonImmutable
    {
        return match ($unit) {
            'day' => $start->addDays($value), 'month' => $start->addMonthsNoOverflow($value), 'year' => $start->addYearsNoOverflow($value)
        };
    }

    public function settle(Invoice $invoice, User $staff, string $method, ?string $reference): void
    {
        DB::transaction(function () use ($invoice, $staff, $method, $reference) {
            Member::whereKey($invoice->member_id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status === 'paid') {
                return;
            }
            $shift = null;
            if ($method === 'cash') {
                $shift = StaffShift::where('active_staff_id', $staff->id)->lockForUpdate()->first();
                if (! $shift) {
                    throw ValidationException::withMessages(['payment' => 'Buka shift kasir sebelum menerima tunai.']);
                }
            }
            Payment::create(['invoice_id' => $invoice->id, 'staff_id' => $staff->id, 'staff_shift_id' => $shift?->id, 'amount_idr' => $invoice->total_idr, 'method' => $method, 'reference' => $reference, 'paid_at' => now()]);
            $latest = MembershipPeriod::where('member_id', $invoice->member_id)->where('status', 'active')->max('ends_at');
            $start = CarbonImmutable::now(config('gym.timezone'));
            if ($latest && CarbonImmutable::parse($latest, 'UTC')->greaterThan($start)) {
                $start = CarbonImmutable::parse($latest, 'UTC')->setTimezone(config('gym.timezone'));
            }
            $end = $this->periodEnd($start, $invoice->snapshot['duration_unit'], $invoice->snapshot['duration_value']);
            MembershipPeriod::create(['member_id' => $invoice->member_id, 'subscription_id' => $invoice->subscription_id, 'source_invoice_id' => $invoice->id, 'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'visit_limit' => $invoice->snapshot['visit_limit'], 'status' => 'active']);
            $invoice->update(['status' => 'paid']);
            DB::table('subscriptions')->where('id', $invoice->subscription_id)->update(['status' => 'active', 'updated_at' => now()]);
            $this->audit('payment.confirmed', $invoice->id, ['method' => $method, 'amount_idr' => $invoice->total_idr]);
        }, 3);
    }

    public function checkin(Member $member, User $staff): ?string
    {
        return DB::transaction(function () use ($member, $staff) {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $period = $member->currentPeriod()->first();
            $reason = match (true) {
                $member->status !== 'active' => 'MEMBERSHIP_SUSPENDED', ! $period => 'MEMBERSHIP_EXPIRED', Checkin::where('member_id', $member->id)->where('checked_in_at', '>', now()->subMinutes(config('gym.duplicate_minutes')))->exists() => 'DUPLICATE_CHECKIN', default => null
            };
            if (! $reason && $period->visit_limit !== null && Checkin::where('membership_period_id', $period->id)->count() >= $period->visit_limit) {
                $reason = 'VISIT_LIMIT_REACHED';
            }
            DB::table('checkin_attempts')->insert(['member_id' => $member->id, 'staff_id' => $staff->id, 'result' => $reason ?? 'SUCCESS', 'attempted_at' => now()]);
            if (! $reason) {
                $checkin = Checkin::create(['member_id' => $member->id, 'membership_period_id' => $period->id, 'staff_id' => $staff->id, 'checked_in_at' => now(), 'method' => 'manual']);
                $this->audit('checkin.created',$checkin->id);
            }

            return $reason;
        }, 3);
    }
}
