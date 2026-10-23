<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipChange;
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
            $invoice = Invoice::create(['invoice_number' => 'INV-'.now()->format('ymd').'-'.strtoupper(Str::random(8)), 'member_id' => $member->id, 'subscription_id' => $subscriptionId, 'total_idr' => $plan->price_idr, 'snapshot' => ['name' => $plan->name, 'plan_type' => $plan->plan_type, 'duration_unit' => $plan->duration_unit, 'duration_value' => $plan->duration_value, 'visit_limit' => $plan->visit_limit, 'price_idr' => $plan->price_idr, 'benefits' => $plan->benefit_definitions], 'request_key' => $key]);
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
            if ($invoice->status !== 'unpaid') {
                throw ValidationException::withMessages(['payment' => 'Invoice ini tidak dapat dibayar.']);
            }
            $change = MembershipChange::where('invoice_id', $invoice->id)->lockForUpdate()->first();
            if ($change && $change->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Permintaan perubahan paket sudah ditutup.']);
            }
            if ($change && $invoice->total_idr === 0) {
                $method = 'credit';
                $reference = 'Penyesuaian nilai sisa paket';
            }
            if (! in_array($method, ['cash', 'bank_transfer', 'qris', 'card', 'other'], true) && ! ($change && $invoice->total_idr === 0 && $method === 'credit')) {
                throw ValidationException::withMessages(['payment' => 'Metode pembayaran tidak sesuai tagihan.']);
            }
            $shift = null;
            if ($method === 'cash') {
                $shift = StaffShift::where('active_staff_id', $staff->id)->lockForUpdate()->first();
                if (! $shift) {
                    throw ValidationException::withMessages(['payment' => 'Buka shift kasir sebelum menerima tunai.']);
                }
            }
            Payment::create(['invoice_id' => $invoice->id, 'staff_id' => $staff->id, 'staff_shift_id' => $shift?->id, 'amount_idr' => $invoice->total_idr, 'method' => $method, 'reference' => $reference, 'paid_at' => now()]);
            $planType = $invoice->snapshot['plan_type'] ?? 'gym';
            $benefits = $invoice->snapshot['benefits'] ?? PackageBenefits::legacy($planType, $invoice->snapshot['visit_limit'] ?? null);
            $keys = array_keys($benefits);
            sort($keys);
            $latest = MembershipPeriod::with('entitlements')->where('member_id', $invoice->member_id)->where('plan_type', $planType)->where('status', 'active')->get()
                ->filter(function (MembershipPeriod $period) use ($keys): bool {
                    $existingKeys = $period->entitlements->isEmpty()
                        ? array_keys(PackageBenefits::legacy($period->plan_type, $period->visit_limit))
                        : $period->entitlements->pluck('benefit_key')->all();
                    sort($existingKeys);

                    return $existingKeys === $keys;
                })->max('ends_at');
            $start = CarbonImmutable::now(config('gym.timezone'));
            if ($latest && CarbonImmutable::parse($latest, 'UTC')->greaterThan($start)) {
                $start = CarbonImmutable::parse($latest, 'UTC')->setTimezone(config('gym.timezone'));
            }
            if ($change) {
                $start = CarbonImmutable::now(config('gym.timezone'));
            }
            $end = $this->periodEnd($start, $invoice->snapshot['duration_unit'], $invoice->snapshot['duration_value']);
            $period = MembershipPeriod::create(['member_id' => $invoice->member_id, 'subscription_id' => $invoice->subscription_id, 'source_invoice_id' => $invoice->id, 'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'visit_limit' => $invoice->snapshot['visit_limit'], 'plan_type' => $planType, 'status' => 'active']);
            app(PackageBenefits::class)->initialize($period, $benefits);
            $invoice->update(['status' => 'paid']);
            if ($change) {
                $change->update(['status' => 'completed', 'activated_at' => $start]);
                $this->audit('membership.change_completed', $change->id, ['new_period_id' => $period->id, 'source_period_id' => $change->source_period_id]);
            }
            DB::table('subscriptions')->where('id', $invoice->subscription_id)->update(['status' => 'active', 'updated_at' => now()]);
            $this->audit('payment.confirmed', $invoice->id, ['method' => $method, 'amount_idr' => $invoice->total_idr]);
        }, 3);
    }

    public function checkin(Member $member, User $staff): ?string
    {
        abort_unless($staff->is_active && in_array($staff->role, ['owner', 'receptionist'], true), 403);

        return DB::transaction(function () use ($member, $staff) {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $deadline = app(OperatingHoursService::class)->checkoutDeadline(CarbonImmutable::now());
            $periods = $member->periods()->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())->orderBy('ends_at')->orderBy('id')->lockForUpdate()->get();
            $entitlement = null;
            $hasAccess = false;
            foreach ($periods as $period) {
                if (! $period->entitlements()->exists()) {
                    app(PackageBenefits::class)->initialize($period, $period->invoice->snapshot['benefits'] ?? PackageBenefits::legacy($period->plan_type, $period->visit_limit));
                }
                $candidate = $period->entitlements()->where('benefit_key', 'gym_access')->lockForUpdate()->first();
                if ($candidate) {
                    $hasAccess = true;
                    if ($candidate->remaining === null || $candidate->remaining > 0) {
                        $entitlement = $candidate;
                        break;
                    }
                }
            }
            $reason = match (true) {
                $member->status !== 'active' => 'MEMBERSHIP_SUSPENDED',
                ! $hasAccess => 'MEMBERSHIP_EXPIRED',
                Checkin::where('member_id', $member->id)->where(fn ($q) => $q->where('checked_in_at', '>', now()->subMinutes(config('gym.duplicate_minutes')))->orWhere(fn ($open) => $open->whereNotNull('checkout_due_at')->where('checkout_due_at', '>', now())->whereNull('checked_out_at')))->exists() => 'DUPLICATE_CHECKIN',
                ! $entitlement => 'VISIT_LIMIT_REACHED',
                default => null,
            };
            DB::table('checkin_attempts')->insert(['member_id' => $member->id, 'staff_id' => $staff->id, 'result' => $reason ?? 'SUCCESS', 'attempted_at' => now()]);
            if (! $reason) {
                $checkin = Checkin::create(['member_id' => $member->id, 'membership_period_id' => $entitlement->membership_period_id, 'membership_entitlement_id' => $entitlement->id, 'staff_id' => $staff->id, 'checked_in_at' => now(), 'checkout_due_at' => $deadline, 'method' => 'manual']);
                $this->audit('checkin.created', $checkin->id);
            }

            return $reason;
        }, 3);
    }
}
