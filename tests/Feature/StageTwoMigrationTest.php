<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\StaffAttendance;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StageTwoMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_preserves_legacy_visits_attendance_prices_and_rights_without_inventing_evidence(): void
    {
        $this->freezeTime();
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $member = Member::factory()->create();
        $invoice = app(MembershipService::class)->createInvoice($member, MembershipPlan::factory()->create(['benefits' => ['gym_access' => 10]]), (string) Str::uuid());
        app(MembershipService::class)->settle($invoice, $owner, 'qris', null);
        app(MembershipService::class)->checkin($member, $owner);
        $visit = Checkin::firstOrFail();
        $attendance = StaffAttendance::create(['user_id' => $owner->id, 'date' => now()->toDateString(), 'clock_in_at' => now()->subHour(), 'clock_out_at' => now(), 'status' => 'present', 'recorded_by' => $owner->id]);
        $trainer = TrainerProfile::factory()->create();
        $invoiceSnapshot = $invoice->snapshot;
        $visitTimestamp = $visit->checked_in_at;
        $attendanceTimestamp = $attendance->clock_in_at;
        $migration = require database_path('migrations/2026_10_23_093806_add_stage_two_operations.php');
        $migration->down();
        $migration->up();
        $this->assertSame($invoiceSnapshot, $invoice->fresh()->snapshot);
        $this->assertTrue($visit->fresh()->checked_in_at->equalTo($visitTimestamp));
        $this->assertNull($visit->fresh()->checked_out_at);
        $this->assertNull($visit->fresh()->checkout_due_at);
        $this->assertTrue($attendance->fresh()->clock_in_at->equalTo($attendanceTimestamp));
        $this->assertNull($attendance->fresh()->clock_in_photo_path);
        $this->assertNull($trainer->fresh()->availability);
        $this->assertDatabaseCount('membership_entitlements', 1);
        $this->assertDatabaseCount('membership_changes', 0);
        $this->assertDatabaseCount('gym_operating_hours', 0);
    }
}
