<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\TrainerAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipBenefitsTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, int|null> $benefits */
    private function purchase(Member $member, array $benefits): MembershipPeriod
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $plan = MembershipPlan::factory()->create(['plan_type' => 'all_access', 'benefits' => $benefits]);
        $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
        app(MembershipService::class)->settle($invoice, $owner, 'qris', null);

        return MembershipPeriod::where('source_invoice_id', $invoice->id)->firstOrFail();
    }

    public function test_bundle_quotas_are_independent_and_repeat_booking_does_not_charge_twice(): void
    {
        $member = Member::factory()->create();
        $period = $this->purchase($member, ['gym_access' => 1, 'pilates_group' => 1, 'gym_personal' => 1]);
        $owner = auth()->user();
        $this->assertNull(app(MembershipService::class)->checkin($member, $owner));
        $pilates = TrainingSession::factory()->create();
        app(TrainingService::class)->enroll($pilates, $member, $owner);
        app(TrainingService::class)->enroll($pilates, $member, $owner);
        $personal = TrainingSession::factory()->create(['activity' => 'gym', 'kind' => 'personal', 'capacity' => 1, 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);
        TrainerAssignment::factory()->create(['member_id' => $member->id, 'trainer_profile_id' => $personal->trainer_profile_id]);
        app(TrainingService::class)->enroll($personal, $member, $owner);
        $this->assertDatabaseCount('class_enrollments', 2);
        foreach ($period->entitlements as $entitlement) {
            $this->assertSame(0, $entitlement->remaining);
        }
        $this->travel(16)->minutes();
        $this->assertSame('VISIT_LIMIT_REACHED', app(MembershipService::class)->checkin($member, $owner));
        $other = TrainingSession::factory()->create(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour()]);
        $this->post(route('classes.enroll', $other), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        app(TrainingService::class)->attendance($pilates->enrollments()->firstOrFail(), 'cancelled', $owner);
        $this->post(route('classes.enroll', $other), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame(0, $period->entitlements()->where('benefit_key', 'gym_access')->firstOrFail()->remaining);
    }

    public function test_wrong_activity_expiry_and_no_package_are_rejected(): void
    {
        $member = Member::factory()->create();
        $period = $this->purchase($member, ['gym_personal' => 2]);
        $session = TrainingSession::factory()->create();
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        $this->assertNull($member->fresh()->currentPeriod);
        $this->assertSame('MEMBERSHIP_EXPIRED', app(MembershipService::class)->checkin($member, auth()->user()));
        $period->update(['ends_at' => now()->addHours(12)]);
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('class_enrollments', 0);
    }

    public function test_purchase_snapshot_survives_catalog_changes_and_settlement_replay(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $member = Member::factory()->create();
        $plan = MembershipPlan::factory()->create(['plan_type' => 'all_access', 'benefits' => ['gym_access' => null, 'pilates_group' => 8]]);
        $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
        $plan->update(['benefits' => ['gym_access' => 1], 'price_idr' => 999999]);
        app(MembershipService::class)->settle($invoice, $owner, 'qris', null);
        app(MembershipService::class)->settle($invoice, $owner, 'qris', null);
        $this->assertSame(['gym_access' => null, 'pilates_group' => 8], $invoice->fresh()->snapshot['benefits']);
        $this->assertDatabaseCount('membership_periods', 1);
        $this->assertDatabaseCount('membership_entitlements', 2);
        $this->assertDatabaseHas('membership_entitlements', ['benefit_key' => 'pilates_group', 'quota' => 8]);
    }

    public function test_owner_can_save_and_edit_explicit_benefits_without_changing_bought_rights(): void
    {
        $member = Member::factory()->create();
        $period = $this->purchase($member, ['gym_access' => 10, 'pilates_personal' => 4]);
        $plan = MembershipPlan::findOrFail(DB::table('subscriptions')->where('id', $period->subscription_id)->value('plan_id'));
        $payload = $plan->only(['name', 'plan_type', 'duration_unit', 'duration_value', 'price_idr', 'is_active']);
        $this->put(route('plans.update', $plan), $payload + ['benefits' => ['pilates_group' => 2]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_entitlements', ['membership_period_id' => $period->id, 'benefit_key' => 'pilates_personal', 'quota' => 4]);
        $this->put(route('plans.update', $plan), $payload + ['benefits' => ['unknown' => 10]])->assertSessionHasErrors('benefits');
        $this->put(route('plans.update', $plan), $payload + ['benefits_editor' => 1])->assertSessionHasErrors('benefits');
        $this->put(route('plans.update', $plan), $payload + ['benefits' => ['gym_access' => 0]])->assertSessionHasErrors('benefits.gym_access');
        $this->get(route('plans'))->assertOk()->assertSee('Pilates grup');
        $this->get(route('members.show', $member))->assertOk()->assertSee('Pilates privat');
    }

    public function test_attendance_consumes_one_credit_and_cancellation_returns_only_reserved_credit(): void
    {
        $member = Member::factory()->create();
        $period = $this->purchase($member, ['pilates_group' => 2]);
        $session = TrainingSession::factory()->create();
        app(TrainingService::class)->enroll($session, $member, auth()->user());
        $entitlement = $period->entitlements()->firstOrFail();
        $enrollment = ClassEnrollment::firstOrFail();
        $this->travelTo($session->starts_at);
        app(TrainingService::class)->attendance($enrollment, 'attended', auth()->user());
        app(TrainingService::class)->attendance($enrollment, 'attended', auth()->user());
        $this->assertSame(1, $entitlement->used);
        $this->assertSame(0, $entitlement->reserved);
        $this->assertSame(1, $entitlement->remaining);
    }

    public function test_package_must_cover_the_entire_session_and_cannot_be_used_before_its_start(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $period = $this->purchase($member, ['pilates_group' => 1]);
        $session = TrainingSession::factory()->create();
        $payload = ['member_id' => $member->id, 'wa_confirmed' => 1];
        $period->update(['ends_at' => $session->starts_at->addMinutes(30)]);
        $this->post(route('classes.enroll', $session), $payload)->assertSessionHasErrors('member_id');
        $period->update(['starts_at' => $session->starts_at->addMinute(), 'ends_at' => $session->ends_at]);
        $this->post(route('classes.enroll', $session), $payload)->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('class_enrollments', 0);
        $period->update(['starts_at' => now()]);
        $this->post(route('classes.enroll', $session), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('class_enrollments', ['member_id' => $member->id, 'membership_period_id' => $period->id]);
    }

    public function test_no_show_uses_credit_and_closing_class_again_does_not_consume_twice(): void
    {
        $this->freezeTime();
        $member = Member::factory()->create();
        $period = $this->purchase($member, ['pilates_group' => 1]);
        $session = TrainingSession::factory()->create();
        app(TrainingService::class)->enroll($session, $member, auth()->user());
        $this->travelTo($session->ends_at);
        app(TrainingService::class)->close($session, 'completed', null, auth()->user());
        app(TrainingService::class)->close($session, 'completed', null, auth()->user());
        $entitlement = $period->entitlements()->firstOrFail();
        $this->assertSame(1, $entitlement->used);
        $this->assertSame(0, $entitlement->remaining);
        $this->assertDatabaseCount('class_enrollments', 1);
        $this->assertDatabaseHas('class_enrollments', ['training_session_id' => $session->id, 'status' => 'no_show']);
    }
}
