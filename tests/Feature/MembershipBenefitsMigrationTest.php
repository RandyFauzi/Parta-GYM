<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\TrainerAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MembershipBenefitsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_preserves_legacy_snapshots_dates_usage_and_bookings_and_backfill_is_repeatable(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $member = Member::factory()->create();
        $plan = MembershipPlan::factory()->create(['plan_type' => 'all_access', 'visit_limit' => 5]);
        $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
        app(MembershipService::class)->settle($invoice, $owner, 'qris', null);
        $period = MembershipPeriod::firstOrFail();
        Checkin::create(['member_id' => $member->id, 'membership_period_id' => $period->id, 'staff_id' => $owner->id, 'checked_in_at' => now(), 'method' => 'manual']);
        $session = TrainingSession::factory()->create(['kind' => 'personal', 'capacity' => 1]);
        $enrollment = ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => $session->id, 'membership_period_id' => $period->id, 'status' => 'attended', 'attended_at' => now()]);
        $before = $period->only(['starts_at', 'ends_at', 'visit_limit', 'status']);
        $snapshot = $invoice->snapshot;
        unset($snapshot['benefits']);
        $invoice->update(['snapshot' => $snapshot]);
        $plan->update(['visit_limit' => 99, 'price_idr' => 999999]);
        $migration = require database_path('migrations/2026_10_23_091027_add_membership_benefits_and_trainer_assignments.php');
        $migration->down();
        $migration->up();
        $migration->backfill();
        $this->assertSame($snapshot, $invoice->fresh()->snapshot);
        $this->assertEquals($before, $period->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('membership_entitlements', 2);
        $this->assertDatabaseCount('trainer_assignments', 1);
        $this->assertDatabaseCount('checkins', 1);
        $this->assertDatabaseCount('payments', 1);
        $gym = $period->entitlements()->where('benefit_key', 'gym_access')->firstOrFail();
        $personal = $period->entitlements()->where('benefit_key', 'legacy_personal')->firstOrFail();
        $this->assertSame(4, $gym->remaining);
        $this->assertSame(4, $personal->remaining);
        $this->assertSame($personal->id, $enrollment->fresh()->membership_entitlement_id);
        $this->assertSame('attended', $enrollment->fresh()->status);
        $this->assertSame('legacy', $enrollment->fresh()->booking_source);
        $assignment = TrainerAssignment::firstOrFail();
        $assignment->update(['revoked_at' => now(), 'active_key' => null]);
        $migration->backfill();
        $this->assertDatabaseCount('trainer_assignments', 1);
        $this->assertFalse($member->fresh()->isCoachedBy($session->trainer->user));
    }

    public function test_old_unpaid_invoice_uses_its_own_quota_after_catalog_edit(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $member = Member::factory()->create();
        $plan = MembershipPlan::factory()->create(['plan_type' => 'pt', 'visit_limit' => 3]);
        $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
        $snapshot = $invoice->snapshot;
        unset($snapshot['benefits']);
        $invoice->update(['snapshot' => $snapshot]);
        $plan->update(['visit_limit' => 100, 'benefits' => ['pilates_personal' => 100]]);
        app(MembershipService::class)->settle($invoice->fresh(), $owner, 'qris', null);
        $this->assertDatabaseHas('membership_entitlements', ['benefit_key' => 'legacy_personal', 'quota' => 3]);
        $this->assertDatabaseMissing('membership_entitlements', ['benefit_key' => 'pilates_personal']);
    }
}
