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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function account(): Member
    {
        $user = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        return $member;
    }

    /** @param array<string, ?int> $benefits */
    private function purchase(Member $member, array $benefits): MembershipPeriod
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $service = app(MembershipService::class);
        $invoice = $service->createInvoice($member, MembershipPlan::factory()->create(['name' => 'Bundle sesuai manfaat', 'plan_type' => 'all_access', 'benefits' => $benefits]), (string) Str::uuid());
        $service->settle($invoice, $owner, 'qris', null);

        return MembershipPeriod::where('source_invoice_id', $invoice->id)->firstOrFail();
    }

    public function test_pilates_only_member_defaults_to_active_pilates_dashboard(): void
    {
        $member = $this->account();
        $this->purchase($member, ['pilates_group' => 8]);
        $this->assertNull($member->fresh()->currentPeriod);
        $this->get(route('portal'))->assertOk()->assertViewHas('dashboardActivity', 'pilates')->assertSee('Akses Pilates Anda')->assertSee('Bundle sesuai manfaat')->assertSee('Hadir Pilates minggu ini')->assertDontSee('gym-athlete.webp');
        $this->get(route('portal.membership'))->assertOk()->assertViewHas('dashboardActivity', 'pilates')->assertSee('Manfaat Pilates tersedia');
        $this->get(route('portal.card'))->assertOk()->assertSee('Akses belum tersedia untuk Gym');
    }

    public function test_switching_bundle_dashboard_does_not_change_independent_quotas_or_purchase_data(): void
    {
        $member = $this->account();
        $period = $this->purchase($member, ['gym_access' => 10, 'pilates_group' => 5, 'gym_personal' => 2]);
        $gymAccess = $period->entitlements()->where('benefit_key', 'gym_access')->firstOrFail();
        $this->assertNull(app(MembershipService::class)->checkin($member, User::factory()->create(['role' => 'owner'])));
        $pilates = $period->entitlements()->where('benefit_key', 'pilates_group')->firstOrFail();
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'membership_period_id' => $period->id, 'membership_entitlement_id' => $pilates->id]);
        $before = $period->fresh()->getAttributes();
        $snapshot = $period->invoice->snapshot;
        $response = $this->get(route('portal', ['activity' => 'gym']))->assertOk();
        $response->assertViewHas('benefits', fn ($benefits) => $benefits->count() === 2 && $benefits->firstWhere('key', 'gym_access')['remaining'] === 9);
        $this->post(route('portal.activity'), ['activity' => 'pilates'])->assertRedirect(route('portal', ['activity' => 'pilates']))->assertSessionHas('portal.activity.'.$member->id, 'pilates');
        $this->get(route('portal'))->assertViewHas('benefits', fn ($benefits) => $benefits->count() === 1 && $benefits->first()['remaining'] === 4 && $benefits->first()['reserved'] === 1);
        $this->assertSame(9, $gymAccess->fresh()->remaining);
        $this->assertSame($before, $period->fresh()->getAttributes());
        $this->assertSame($snapshot, $period->invoice->fresh()->snapshot);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('class_enrollments', 1);
    }

    public function test_modern_benefits_count_consumption_reservations_and_cancellations_separately(): void
    {
        $member = $this->account();
        $period = $this->purchase($member, ['pilates_group' => 3]);
        $entitlement = $period->entitlements->first();
        foreach (['attended', 'no_show', 'confirmed', 'cancelled'] as $status) {
            ClassEnrollment::factory()->create(['member_id' => $member->id, 'membership_period_id' => $period->id, 'membership_entitlement_id' => $entitlement->id, 'status' => $status]);
        }
        $this->get(route('portal'))->assertOk()->assertSee('Kuota habis')->assertViewHas('benefits', fn ($benefits) => $benefits->first()['used'] === 2 && $benefits->first()['reserved'] === 1 && $benefits->first()['remaining'] === 0);
    }

    public function test_legacy_private_quota_stays_shared_in_both_modes_without_backfilling_data(): void
    {
        $member = $this->account();
        $period = $this->purchase($member, ['legacy_personal' => 3]);
        $period->entitlements()->delete();
        foreach (['gym' => 'confirmed', 'pilates' => 'attended'] as $activity => $status) {
            ClassEnrollment::factory()->create(['member_id' => $member->id, 'membership_period_id' => $period->id, 'training_session_id' => TrainingSession::factory()->create(['kind' => 'personal', 'activity' => $activity])->id, 'status' => $status]);
        }
        foreach (['gym', 'pilates'] as $activity) {
            $this->get(route('portal', ['activity' => $activity]))->assertOk()->assertSee('dipakai bersama Gym dan Pilates')->assertViewHas('benefits', fn ($benefits) => $benefits->first()['remaining'] === 1 && $benefits->first()['shared'] === true);
        }
        $this->assertDatabaseCount('membership_entitlements', 0);
    }

    public function test_expired_future_and_replaced_periods_are_not_reported_as_active(): void
    {
        $member = $this->account();
        $period = $this->purchase($member, ['gym_access' => null, 'pilates_group' => 8]);
        $period->update(['ends_at' => now()]);
        $this->get(route('portal', ['activity' => 'pilates']))->assertOk()->assertSee('Belum ada manfaat Pilates aktif')->assertViewHas('benefits', fn ($benefits) => $benefits->isEmpty());
        $period->update(['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(10)]);
        $this->get(route('portal'))->assertViewHas('benefits', fn ($benefits) => $benefits->isEmpty());
        $period->update(['starts_at' => now()->subDay(), 'status' => 'replaced']);
        $this->get(route('portal'))->assertViewHas('benefits', fn ($benefits) => $benefits->isEmpty());
        $period->update(['status' => 'active']);
        $this->get(route('portal', ['activity' => 'gym']))->assertSee('Tanpa batas')->assertViewHas('benefits', fn ($benefits) => $benefits->first()['remaining'] === null);
        $member->update(['status' => 'suspended']);
        $this->get(route('portal'))->assertSee('Akses member sedang ditangguhkan');
    }

    public function test_dashboard_lists_only_owned_upcoming_sessions_and_assigned_active_trainer(): void
    {
        $member = $this->account();
        $session = TrainingSession::factory()->create(['title' => 'My Pilates reservation']);
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => $session->id]);
        TrainerAssignment::factory()->create(['member_id' => $member->id, 'activity' => 'pilates', 'trainer_profile_id' => $session->trainer_profile_id]);
        ClassEnrollment::factory()->create(['training_session_id' => TrainingSession::factory()->create(['title' => 'Other member private session', 'kind' => 'personal'])->id]);
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => TrainingSession::factory()->create(['title' => 'My Gym session', 'activity' => 'gym'])->id]);
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'status' => 'cancelled', 'training_session_id' => TrainingSession::factory()->create(['title' => 'Cancelled reservation'])->id]);
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => TrainingSession::factory()->create(['title' => 'Cancelled class', 'status' => 'cancelled'])->id]);
        $this->get(route('portal', ['activity' => 'pilates']))->assertOk()->assertSee('My Pilates reservation')->assertSee($session->trainer->user->name)->assertDontSee('Other member private session')->assertDontSee('My Gym session')->assertDontSee('Cancelled reservation')->assertDontSee('Cancelled class')->assertViewHas('upcoming', fn ($upcoming) => $upcoming->count() === 1);
    }

    public function test_pilates_activity_week_uses_local_attendance_dates_and_excludes_gym_and_no_show(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-23 00:30', 'Asia/Makassar'));
        $member = $this->account();
        foreach ([['pilates', 'attended', now()->subHour()], ['pilates', 'attended', now()], ['pilates', 'no_show', now()], ['gym', 'attended', now()]] as [$activity, $status, $at]) {
            ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => TrainingSession::factory()->create(['activity' => $activity])->id, 'status' => $status, 'attended_at' => $at]);
        }
        $this->get(route('portal', ['activity' => 'pilates']))->assertOk()->assertViewHas('weekVisits', 2)->assertViewHas('totalVisits', 2)->assertViewHas('activity', fn ($days) => $days[5]['count'] === 1 && $days[6]['count'] === 1);
        $this->get(route('portal', ['activity' => 'gym']))->assertViewHas('weekVisits', 0);
    }

    public function test_catalog_filters_by_actual_benefits_and_shows_bundles_in_both_modes(): void
    {
        $this->account();
        MembershipPlan::factory()->create(['name' => 'Gym Only Catalog', 'benefits' => ['gym_access' => null]]);
        MembershipPlan::factory()->create(['name' => 'Pilates Only Catalog', 'benefits' => ['pilates_personal' => 5], 'plan_type' => 'gym']);
        MembershipPlan::factory()->create(['name' => 'Bundle Both Catalog', 'benefits' => ['gym_access' => 10, 'pilates_group' => 4]]);
        $this->get(route('portal.membership', ['activity' => 'pilates']))->assertOk()->assertSee('Pilates Only Catalog')->assertSee('Bundle Both Catalog')->assertDontSee('Gym Only Catalog');
        $this->get(route('portal.membership', ['activity' => 'gym']))->assertOk()->assertSee('Gym Only Catalog')->assertSee('Bundle Both Catalog')->assertDontSee('Pilates Only Catalog');
    }

    public function test_booking_whatsapp_is_optional_and_activity_specific_without_creating_booking(): void
    {
        $this->account();
        config(['gym.booking_whatsapp' => null]);
        $this->get(route('portal', ['activity' => 'pilates']))->assertSee('Nomor WhatsApp booking belum diatur')->assertDontSee('https://wa.me/');
        config(['gym.booking_whatsapp' => '081234567890']);
        $this->get(route('portal', ['activity' => 'pilates']))->assertSee('https://wa.me/6281234567890?text=')->assertSee('booking%20Pilates');
        $this->assertDatabaseCount('class_enrollments', 0);
    }

    public function test_switch_is_validated_scoped_to_logged_in_member_and_never_grants_access(): void
    {
        $member = $this->account();
        $this->post(route('portal.activity'), ['activity' => 'invalid'])->assertSessionHasErrors('activity');
        $this->get(route('portal', ['activity' => ['gym']]))->assertSessionHasErrors('activity');
        $this->post(route('portal.activity'), ['activity' => 'pilates', 'member_id' => (string) Str::uuid()])->assertSessionHas('portal.activity.'.$member->id, 'pilates');
        $this->get(route('portal'))->assertViewHas('dashboardActivity', 'pilates')->assertViewHas('benefits', fn ($benefits) => $benefits->isEmpty());
        $this->post(route('classes.enroll', TrainingSession::factory()->create()), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertForbidden();
        $this->assertDatabaseCount('membership_periods', 0);
        $this->assertDatabaseCount('membership_entitlements', 0);
        $other = $this->account();
        $this->get(route('portal'))->assertViewHas('dashboardActivity', 'gym');
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->post(route('portal.activity'), ['activity' => 'pilates'])->assertForbidden();
    }
}
