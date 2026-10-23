<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\StaffAttendance;
use App\Models\TrainerAssignment;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\TrainingService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalAuditTest extends TestCase
{
    use RefreshDatabase;

    private function buy(Member $member, string $type, ?int $limit = null): MembershipPeriod
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $plan = MembershipPlan::factory()->create(['plan_type' => $type, 'visit_limit' => $limit, 'duration_unit' => 'day', 'duration_value' => 30]);
        $service = app(MembershipService::class);
        $invoice = $service->createInvoice($member, $plan, (string) Str::uuid());
        $service->settle($invoice, $owner, 'qris', null);

        return MembershipPeriod::where('source_invoice_id', $invoice->id)->firstOrFail();
    }

    public function test_pt_purchase_is_independent_of_gym_access_and_snapshots_type(): void
    {
        $member = Member::factory()->create();
        $pt = $this->buy($member, 'pt', 15);
        $this->assertNull($member->fresh()->currentPeriod);
        $gym = $this->buy($member, 'gym');
        $this->assertTrue($gym->starts_at->diffInSeconds($pt->starts_at) < 10);
        $this->assertSame($gym->id, $member->fresh()->currentPeriod->id);
        $this->assertSame('pt', $pt->invoice->snapshot['plan_type']);
        $this->assertSame(30, (int) $pt->starts_at->diffInDays($pt->ends_at));
    }

    public function test_personal_booking_reserves_quota_once_and_cancellation_releases_it(): void
    {
        $member = Member::factory()->create();
        $period = $this->buy($member, 'pt', 1);
        $owner = auth()->user();
        $session = TrainingSession::factory()->create(['kind' => 'personal', 'capacity' => 1]);
        TrainerAssignment::factory()->create(['member_id' => $member->id, 'trainer_profile_id' => $session->trainer_profile_id, 'activity' => $session->activity]);
        $service = app(TrainingService::class);
        $service->enroll($session, $member, $owner);
        $service->enroll($session, $member, $owner);
        $this->assertDatabaseHas('class_enrollments', ['membership_period_id' => $period->id, 'member_id' => $member->id]);
        $this->assertDatabaseCount('class_enrollments', 1);
        $next = TrainingSession::factory()->create(['kind' => 'personal', 'capacity' => 1, 'trainer_profile_id' => $session->trainer_profile_id, 'starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);
        $this->post(route('classes.enroll', $next), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        $service->close($session, 'cancelled', 'Studio maintenance', $owner);
        $this->post(route('classes.enroll', $next), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasNoErrors();
    }

    public function test_expired_pt_package_cannot_book_a_future_session(): void
    {
        $member = Member::factory()->create();
        $period = $this->buy($member, 'pt', 10);
        $session = TrainingSession::factory()->create(['kind' => 'personal', 'capacity' => 1, 'starts_at' => $period->ends_at->addDay(), 'ends_at' => $period->ends_at->addDay()->addHour()]);
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('class_enrollments', 0);
    }

    public function test_pt_cannot_read_or_write_unassigned_member_progress(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $member = Member::factory()->create();
        $record = MemberProgressRecord::create(['member_id' => $member->id, 'trainer_id' => User::factory()->create(['role' => 'owner'])->id, 'recorded_at' => now(), 'workout_summary' => 'Confidential report']);
        $this->actingAs($trainer->user);
        $this->post(route('members.progress.store', $member), ['recorded_at' => now()->toDateTimeString(), 'workout_summary' => 'Not assigned'])->assertForbidden();
        $this->get(route('members.progress.show', [$member, $record]))->assertForbidden();
        $this->get(route('coaching.member', $member))->assertForbidden();
        $session = TrainingSession::factory()->create(['trainer_profile_id' => $trainer->id]);
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => $session->id]);
        $this->get(route('coaching.member', $member))->assertForbidden();
        TrainerAssignment::factory()->create(['member_id' => $member->id, 'trainer_profile_id' => $trainer->id, 'activity' => $session->activity]);
        $this->get(route('coaching.member', $member))->assertOk()->assertSee('Confidential report');
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get(route('members.progress.show', [$member, $record]))->assertForbidden();
    }

    public function test_manual_attendance_rejects_member_accounts_and_reversed_times(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $date = now()->subDay()->format('Y-m-d');
        $data = ['date' => $date, 'status' => 'present', 'clock_in_time' => '12:00', 'clock_out_time' => '10:00'];
        $this->post(route('staff.attendance.manual'), $data + ['user_id' => User::factory()->create(['role' => 'member'])->id])->assertSessionHasErrors('user_id');
        $this->post(route('staff.attendance.manual'), $data + ['user_id' => User::factory()->create(['role' => 'personal_trainer'])->id])->assertSessionHasErrors('clock_out_time');
        $this->get(route('staff.attendance.me', ['month' => 'bad-date']))->assertSessionHasErrors('month');
    }

    public function test_staff_cannot_overwrite_approved_sick_leave_and_can_close_overnight_shift(): void
    {
        $user = User::factory()->create(['role' => 'receptionist']);
        $this->actingAs($user);
        $this->travelTo(now()->timezone(config('gym.timezone'))->startOfDay()->addHours(23));
        $this->post(route('staff.clock-in'))->assertSessionHas('success');
        $this->travel(2)->hours();
        $this->post(route('staff.clock-out'))->assertSessionHas('success');
        $this->assertNotNull(StaffAttendance::firstOrFail()->clock_out_at);
        StaffAttendance::create(['user_id' => $user->id, 'date' => now()->timezone(config('gym.timezone'))->toDateString(), 'status' => 'sick', 'recorded_by' => $user->id]);
        $this->post(route('staff.clock-in'))->assertSessionHas('error');
        $this->assertDatabaseHas('staff_attendances', ['user_id' => $user->id, 'status' => 'sick', 'clock_in_at' => null]);
    }

    public function test_whatsapp_card_uses_member_portal_and_unpaid_invoice_is_never_labeled_paid(): void
    {
        $member = Member::factory()->create(['user_id' => User::factory()->create(['role' => 'member'])->id]);
        $wa = app(WhatsAppService::class);
        $message = $wa->getMemberPassMessage($member);
        $this->assertStringContainsString(route('portal.card'), $message);
        $this->assertStringNotContainsString(route('members.show', $member), $message);
        $invoice = app(MembershipService::class)->createInvoice($member, MembershipPlan::factory()->create(), (string) Str::uuid());
        $this->assertStringContainsString('BELUM DIBAYAR', $wa->getReceiptMessage($invoice));
        $this->assertStringNotContainsString('*LUNAS*', $wa->getReceiptMessage($invoice));
    }

    public function test_frontdesk_shows_daily_count_and_latest_checkin(): void
    {
        $member = Member::factory()->create(['full_name' => 'Repeat visitor']);
        $this->buy($member, 'gym');
        $this->assertNull(app(MembershipService::class)->checkin($member, auth()->user()));
        $this->get('/frontdesk/checkin?q=Repeat')->assertOk()->assertSee('1x hari ini');
    }

    public function test_returning_member_search_accepts_local_phone_format(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'receptionist']));
        Member::factory()->create(['phone' => '+628123456789', 'full_name' => 'Existing customer']);
        $this->get('/members/new?search_returning=08123456789')->assertOk()->assertSee('Existing customer');
    }

    public function test_historical_progress_detail_works_for_own_member_portal(): void
    {
        $account = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $account->id]);
        $trainer = User::factory()->create(['role' => 'personal_trainer']);
        $record = MemberProgressRecord::create(['member_id' => $member->id, 'trainer_id' => $trainer->id, 'recorded_at' => now(), 'workout_summary' => 'Workout history']);
        $trainer->delete();
        $this->actingAs($account)->get(route('members.progress.show', [$member, $record]))->assertOk()->assertSee('Workout history');
    }
}
