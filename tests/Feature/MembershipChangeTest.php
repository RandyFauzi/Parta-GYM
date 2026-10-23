<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\MembershipChange;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MembershipChangeService;
use App\Services\MembershipService;
use App\Services\TrainingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipChangeTest extends TestCase
{
    use RefreshDatabase;

    private function buy(Member $member, array $benefits, int $price = 300000): MembershipPeriod
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $plan = MembershipPlan::factory()->create(['price_idr' => $price, 'plan_type' => 'all_access', 'duration_unit' => 'day', 'duration_value' => 30, 'benefits' => $benefits]);
        $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
        app(MembershipService::class)->settle($invoice, auth()->user(), 'qris', null);

        return MembershipPeriod::where('source_invoice_id', $invoice->id)->firstOrFail();
    }

    private function payload(MembershipPeriod $source, MembershipPlan $target): array
    {
        return ['source_period_id' => $source->id, 'plan_id' => $target->id,
            'quote' => app(MembershipChangeService::class)->preview($source, $target)['quote'],
            'reason' => 'Perubahan program latihan', 'request_key' => (string) Str::uuid(),
            'method' => 'qris', 'confirmed' => 1];
    }

    public function test_upgrade_prorates_remaining_time_and_charges_only_difference_without_rewriting_purchase(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $member = Member::factory()->create();
        $source = $this->buy($member, ['gym_access' => 10]);
        $snapshot = $source->invoice->snapshot;
        $originalEnd = $source->ends_at;
        app(MembershipService::class)->checkin($member, auth()->user());
        $this->travel(16)->minutes();
        app(MembershipService::class)->checkin($member, auth()->user());
        $this->travelTo($source->starts_at->addDays(15));
        $target = MembershipPlan::factory()->create(['price_idr' => 500000, 'benefits' => ['gym_access' => null, 'gym_personal' => 4]]);
        $payload = $this->payload($source, $target);
        $this->get(route('membership-changes.create', [$member, 'source_period_id' => $source->id, 'plan_id' => $target->id]))->assertOk()->assertSee('350.000')->assertSee('150.000');
        $this->post(route('membership-changes.store', $member), $payload)->assertSessionHasNoErrors();
        $change = MembershipChange::firstOrFail();
        $this->assertSame(150000, $change->source_credit_idr);
        $this->assertSame(350000, $change->amount_due_idr);
        $this->assertSame('completed', $change->status);
        $this->assertSame('replaced', $source->fresh()->status);
        $this->assertTrue($originalEnd->equalTo($source->fresh()->ends_at));
        $this->assertSame($snapshot, $source->invoice->fresh()->snapshot);
        $this->assertDatabaseHas('payments', ['invoice_id' => $change->invoice_id, 'amount_idr' => 350000]);
        $this->assertDatabaseHas('membership_periods', ['source_invoice_id' => $change->invoice_id, 'status' => 'active']);
        $this->assertDatabaseCount('checkins', 2);
        $this->post(route('membership-changes.store', $member), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('membership_changes', 1);
        $this->assertDatabaseCount('member_credit_entries', 1);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_downgrade_keeps_excess_credit_and_next_change_can_spend_it_without_fake_cash_payment(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $source = $this->buy($member, ['gym_access' => 10]);
        $this->travelTo($source->starts_at->addDays(15));
        $cheap = MembershipPlan::factory()->create(['price_idr' => 50000, 'benefits' => ['gym_access' => 5]]);
        $this->post(route('membership-changes.store', $member), $this->payload($source, $cheap))->assertSessionHasNoErrors();
        $change = MembershipChange::firstOrFail();
        $this->assertSame(0, $change->amount_due_idr);
        $this->assertSame(100000, app(MembershipChangeService::class)->balance($member->id));
        $this->assertDatabaseHas('payments', ['invoice_id' => $change->invoice_id, 'amount_idr' => 0, 'method' => 'credit']);
        $this->get(route('invoices.show', $change->invoice_id))->assertOk()->assertSee('Penyesuaian paket');
        $nextSource = MembershipPeriod::where('source_invoice_id', $change->invoice_id)->firstOrFail();
        $nextPlan = MembershipPlan::factory()->create(['price_idr' => 120000, 'benefits' => ['gym_access' => null]]);
        $this->post(route('membership-changes.store', $member), $this->payload($nextSource, $nextPlan))->assertSessionHasNoErrors();
        $this->assertSame(30000, app(MembershipChangeService::class)->balance($member->id));
        $this->assertDatabaseCount('membership_changes', 2);
        $this->assertDatabaseCount('member_credit_entries', 2);
        $this->assertDatabaseCount('staff_shifts', 0);
    }

    public function test_consumed_quota_limits_credit_and_bundle_value_is_allocated_per_benefit(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $source = $this->buy($member, ['gym_access' => 10]);
        $entitlement = $source->entitlements()->firstOrFail();
        foreach (range(1, 8) as $index) {
            Checkin::create(['member_id' => $member->id, 'membership_period_id' => $source->id, 'membership_entitlement_id' => $entitlement->id, 'staff_id' => auth()->id(), 'checked_in_at' => now(), 'method' => 'manual']);
        }
        $target = MembershipPlan::factory()->create(['price_idr' => 500000, 'benefits' => ['gym_access' => null]]);
        $this->assertSame(60000, app(MembershipChangeService::class)->preview($source, $target)['source_credit_idr']);
        $bundleMember = Member::factory()->create();
        $bundle = $this->buy($bundleMember, ['gym_access' => null, 'pilates_group' => 8]);
        $this->travelTo($bundle->starts_at->addDays(15));
        $this->assertSame(150000, app(MembershipChangeService::class)->preview($bundle, $target)['source_credit_idr']);
    }

    public function test_open_bookings_are_moved_to_new_rights_and_incompatible_changes_are_rejected(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $source = $this->buy($member, ['pilates_group' => 2], 100000);
        $session = TrainingSession::factory()->create();
        app(TrainingService::class)->enroll($session, $member, auth()->user());
        $enrollment = ClassEnrollment::firstOrFail();
        $wrong = MembershipPlan::factory()->create(['benefits' => ['gym_access' => null]]);
        $this->post(route('membership-changes.store', $member), $this->payload($source, $wrong))->assertSessionHasErrors('plan_id');
        $this->assertDatabaseCount('membership_changes', 0);
        $this->assertSame($source->id, $enrollment->fresh()->membership_period_id);
        $target = MembershipPlan::factory()->create(['price_idr' => 150000, 'benefits' => ['pilates_group' => 3]]);
        $this->post(route('membership-changes.store', $member), $this->payload($source, $target))->assertSessionHasNoErrors();
        $change = MembershipChange::firstOrFail();
        $period = MembershipPeriod::where('source_invoice_id', $change->invoice_id)->firstOrFail();
        $this->assertSame($period->id, $enrollment->fresh()->membership_period_id);
        $this->assertSame('confirmed', $enrollment->fresh()->status);
        $this->assertSame(2, $period->entitlements()->firstOrFail()->remaining);
        $this->assertSame($source->id, $change->source_snapshot['bookings'][0]['membership_period_id']);
    }

    public function test_stale_or_modified_quotes_and_failed_cash_settlement_leave_old_membership_untouched(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $source = $this->buy($member, ['gym_access' => 10], 100000);
        $target = MembershipPlan::factory()->create(['price_idr' => 500000, 'benefits' => ['gym_access' => null]]);
        $payload = $this->payload($source, $target);
        $this->post(route('membership-changes.store', $member), [...$payload, 'quote' => 'tampered'])->assertSessionHasErrors('quote');
        $target->update(['price_idr' => 600000]);
        $this->post(route('membership-changes.store', $member), $payload)->assertSessionHasErrors('quote');
        $payload = $this->payload($source, $target->fresh());
        $this->travel(11)->minutes();
        $this->post(route('membership-changes.store', $member), $payload)->assertSessionHasErrors('quote');
        $payload = $this->payload($source, $target->fresh());
        $this->post(route('membership-changes.store', $member), [...$payload, 'method' => 'cash'])->assertSessionHasErrors('payment');
        $this->assertSame('active', $source->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('membership_changes', 0);
        $this->assertDatabaseCount('member_credit_entries', 0);
    }

    public function test_change_permissions_and_member_ownership_are_enforced(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $source = $this->buy($member, ['gym_access' => 10]);
        $owner = auth()->user();
        $target = MembershipPlan::factory()->create(['benefits' => ['gym_access' => null]]);
        $payload = $this->payload($source, $target);
        $other = Member::factory()->create();
        $this->post(route('membership-changes.store', $other), $payload)->assertNotFound();
        foreach (['receptionist', 'personal_trainer', 'member'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->post(route('membership-changes.store', $member), $payload)->assertForbidden();
        }
        $this->actingAs($owner)->post(route('membership-changes.store', $member), [])->assertSessionHasErrors(['source_period_id', 'plan_id', 'quote', 'method', 'confirmed']);
        $this->assertDatabaseCount('membership_changes', 0);
    }
}
