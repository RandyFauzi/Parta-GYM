<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\ClassEnrollment;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberCreditEntry;
use App\Models\MembershipChange;
use App\Models\MembershipEntitlement;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipChangeService
{
    /** @return array<string, mixed> */
    public function preview(MembershipPeriod $source, MembershipPlan $target): array
    {
        $at = CarbonImmutable::now();
        $sourceData = $this->sourceData($source);
        $price = (int) ($source->invoice->snapshot['price_idr'] ?? $source->invoice->total_idr);
        $benefits = $sourceData['benefits'];
        $seconds = max(1, $source->ends_at->getTimestamp() - $source->starts_at->getTimestamp());
        $remainingSeconds = max(0, min($seconds, $source->ends_at->getTimestamp() - $at->getTimestamp()));
        $credit = 0;
        foreach ($benefits as $index => $benefit) {
            $allocatedPrice = intdiv($price, count($benefits)) + ($index < $price % count($benefits) ? 1 : 0);
            $value = intdiv($allocatedPrice * $remainingSeconds, $seconds);
            if ($benefit['quota'] !== null) {
                $unused = max(0, $benefit['quota'] - $benefit['used']);
                $value = min($value, intdiv($allocatedPrice * $unused, max(1, $benefit['quota'])));
            }
            $credit += $value;
        }
        $wallet = $this->balance($source->member_id);
        $applied = min($target->price_idr, $credit + $wallet);
        $data = [
            'source' => $sourceData, 'target' => $this->targetData($target), 'quoted_at' => $at->toIso8601String(),
            'source_credit_idr' => $credit, 'wallet_before_idr' => $wallet, 'applied_credit_idr' => $applied,
            'amount_due_idr' => $target->price_idr - $applied, 'wallet_after_idr' => $wallet + $credit - $applied,
        ];
        $data['quote'] = Crypt::encryptString(json_encode($data, JSON_THROW_ON_ERROR));

        return $data;
    }

    public function balance(string $memberId): int
    {
        return (int) MemberCreditEntry::where('member_id', $memberId)->sum('amount_idr');
    }

    /** @return array<string, mixed> */
    private function sourceData(MembershipPeriod $source): array
    {
        $source->load('invoice', 'entitlements');
        $definitions = $source->invoice->snapshot['benefits'] ?? PackageBenefits::legacy($source->plan_type, $source->visit_limit);
        $benefits = [];
        foreach ($definitions as $key => $quota) {
            $entitlement = $source->entitlements->firstWhere('benefit_key', $key);
            $used = $entitlement ? $entitlement->used : ($key === 'gym_access' ? Checkin::where('membership_period_id', $source->id)->count() : $source->classEnrollments()->whereIn('status', ['attended', 'no_show'])->count());
            $reserved = $entitlement?->reserved ?? $source->classEnrollments()->where('status', 'confirmed')->count();
            $benefits[] = ['key' => $key, 'quota' => $quota === null ? null : (int) $quota, 'used' => $used, 'reserved' => $reserved];
        }

        return [
            'period_id' => $source->id, 'member_id' => $source->member_id, 'status' => $source->status,
            'name' => $source->invoice->snapshot['name'], 'price_idr' => $source->invoice->snapshot['price_idr'] ?? $source->invoice->total_idr,
            'starts_at' => $source->starts_at->toIso8601String(), 'ends_at' => $source->ends_at->toIso8601String(), 'benefits' => $benefits,
        ];
    }

    /** @return array<string, mixed> */
    private function targetData(MembershipPlan $target): array
    {
        return ['plan_id' => $target->id, 'name' => $target->name, 'price_idr' => $target->price_idr, 'benefits' => $target->benefit_definitions, 'duration_unit' => $target->duration_unit, 'duration_value' => $target->duration_value, 'is_active' => $target->is_active];
    }

    public function replace(Member $member, MembershipPeriod $source, MembershipPlan $target, User $actor, string $quote, string $reason, string $key, string $method, ?string $reference): MembershipChange
    {
        abort_unless($actor->hasPermission('memberships.change'), 403);

        return DB::transaction(function () use ($member, $source, $target, $actor, $quote, $reason, $key, $method, $reference): MembershipChange {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $existing = MembershipChange::where('request_key', $key)->first();
            if ($existing) {
                $planId = DB::table('subscriptions')->where('id', $existing->invoice->subscription_id)->value('plan_id');
                abort_unless($existing->member_id === $member->id && $existing->source_period_id === $source->id && $planId === $target->id, 409);

                return $existing;
            }
            abort_if(Invoice::where('request_key', $key)->exists(), 409);
            $source = MembershipPeriod::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $target = MembershipPlan::whereKey($target->id)->lockForUpdate()->firstOrFail();
            abort_unless($source->member_id === $member->id, 404);
            if ($member->status !== 'active' || $source->status !== 'active' || $source->starts_at->gt(now()) || $source->ends_at->lte(now()) || ! $target->is_active) {
                throw ValidationException::withMessages(['source_period_id' => 'Pilih periode aktif dan paket tujuan yang tersedia.']);
            }
            try {
                $pricing = json_decode(Crypt::decryptString($quote), true, 512, JSON_THROW_ON_ERROR);
            } catch (DecryptException|\JsonException) {
                throw ValidationException::withMessages(['quote' => 'Pratinjau tidak valid. Muat ulang perhitungan.']);
            }
            $quotedAt = CarbonImmutable::parse($pricing['quoted_at']);
            if ($quotedAt->gt(now()) || $quotedAt->lt(now()->subMinutes(10)) ||
                $pricing['source'] !== $this->sourceData($source) || $pricing['target'] !== $this->targetData($target) ||
                $pricing['wallet_before_idr'] !== $this->balance($member->id)) {
                throw ValidationException::withMessages(['quote' => 'Data atau harga telah berubah, atau pratinjau kedaluwarsa. Periksa ulang perhitungan.']);
            }
            $enrollments = ClassEnrollment::where('membership_period_id', $source->id)->where('status', 'confirmed')->orderBy('id')->get();
            $sessions = TrainingSession::whereIn('id', $enrollments->pluck('training_session_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $newEnd = app(MembershipService::class)->periodEnd(CarbonImmutable::now(), $target->duration_unit, $target->duration_value);
            $mapping = [];
            $reserved = [];
            foreach ($enrollments as $enrollment) {
                $session = $sessions->get($enrollment->training_session_id);
                $benefitKey = $session->activity.'_'.$session->kind;
                if (! array_key_exists($benefitKey, $target->benefit_definitions) && $session->kind === 'personal' && array_key_exists('legacy_personal', $target->benefit_definitions)) {
                    $benefitKey = 'legacy_personal';
                }
                $reserved[$benefitKey] = ($reserved[$benefitKey] ?? 0) + 1;
                $quota = $target->benefit_definitions[$benefitKey] ?? null;
                if ($session->status !== 'scheduled' || $session->starts_at->lt(now()) || $session->ends_at->gt($newEnd) ||
                    ! array_key_exists($benefitKey, $target->benefit_definitions) || ($quota !== null && $reserved[$benefitKey] > $quota)) {
                    throw ValidationException::withMessages(['plan_id' => 'Booking yang masih terbuka tidak cocok dengan manfaat, kuota, atau masa berlaku paket baru. Pindahkan atau batalkan booking terlebih dahulu.']);
                }
                $mapping[$enrollment->id] = $benefitKey;
            }
            $invoice = app(MembershipService::class)->createInvoice($member, $target, $key);
            $snapshot = $invoice->snapshot;
            $snapshot['adjustment'] = array_intersect_key($pricing, array_flip(['source_credit_idr', 'applied_credit_idr', 'amount_due_idr', 'wallet_before_idr', 'wallet_after_idr', 'quoted_at']));
            $invoice->update(['total_idr' => $pricing['amount_due_idr'], 'snapshot' => $snapshot]);
            $sourceSnapshot = $pricing['source'];
            $sourceSnapshot['bookings'] = $enrollments->map(fn (ClassEnrollment $enrollment): array => $enrollment->only(['id', 'training_session_id', 'membership_period_id', 'membership_entitlement_id']))->all();
            $change = MembershipChange::create([
                'member_id' => $member->id, 'source_period_id' => $source->id, 'invoice_id' => $invoice->id,
                'requested_by' => $actor->id, 'reason' => $reason, 'source_snapshot' => $sourceSnapshot, 'request_key' => $key,
                'source_credit_idr' => $pricing['source_credit_idr'], 'applied_credit_idr' => $pricing['applied_credit_idr'], 'amount_due_idr' => $pricing['amount_due_idr'],
            ]);
            app(MembershipService::class)->settle($invoice, $actor, $method, $reference);
            $period = MembershipPeriod::where('source_invoice_id', $invoice->id)->firstOrFail();
            foreach ($mapping as $enrollmentId => $benefitKey) {
                $entitlement = MembershipEntitlement::where('membership_period_id', $period->id)->where('benefit_key', $benefitKey)->firstOrFail();
                ClassEnrollment::whereKey($enrollmentId)->update(['membership_period_id' => $period->id, 'membership_entitlement_id' => $entitlement->id]);
            }
            $source->update(['status' => 'replaced']);
            MemberCreditEntry::create(['member_id' => $member->id, 'membership_change_id' => $change->id, 'amount_idr' => $pricing['source_credit_idr'] - $pricing['applied_credit_idr']]);
            app(MembershipService::class)->audit('membership.replaced', $change->id, ['source_period_id' => $source->id, 'new_period_id' => $period->id, 'credit' => $pricing['source_credit_idr'], 'due' => $pricing['amount_due_idr'], 'wallet_after' => $pricing['wallet_after_idr'], 'reason' => $reason]);

            return $change->fresh();
        }, 3);
    }
}
