<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Models\MembershipPlan;
use App\Models\TrainerAssignment;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagementBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_assigns_and_revokes_coach_access_without_erasing_reports(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $trainer = TrainerProfile::factory()->create();
        $member = Member::factory()->create();
        $payload = ['trainer_profile_id' => $trainer->id, 'activity' => 'gym', 'reason' => 'Program strength member'];
        $this->actingAs($owner)->post(route('trainer-assignments.store', $member), $payload)->assertSessionHasNoErrors();
        $this->post(route('trainer-assignments.store', $member), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('trainer_assignments', 1);
        $this->actingAs($trainer->user)->get(route('coaching.member', $member))->assertOk();
        $this->post(route('members.progress.store', $member), ['recorded_at' => now()->timezone(config('gym.timezone'))->format('Y-m-d H:i:s'), 'workout_summary' => 'Program berjalan baik'])->assertSessionHasNoErrors();
        $record = MemberProgressRecord::firstOrFail();
        $assignment = TrainerAssignment::firstOrFail();
        $this->actingAs($owner)->delete(route('trainer-assignments.destroy', $assignment), ['reason' => 'Ganti coach program'])->assertSessionHasNoErrors();
        $this->actingAs($trainer->user)->get(route('coaching.member', $member))->assertForbidden();
        $this->delete(route('members.progress.destroy', $record))->assertForbidden();
        $this->get(route('members.progress.show', [$member, $record]))->assertForbidden();
        $this->get(route('coaching.members'))->assertDontSee($member->full_name);
        $this->assertDatabaseHas('member_progress_records', ['id' => $record->id]);
    }

    public function test_member_and_trainer_cannot_choose_coach_or_schedule(): void
    {
        $member = Member::factory()->create();
        $trainer = TrainerProfile::factory()->create();
        foreach ([$trainer->user, User::factory()->create(['role' => 'member']), User::factory()->create(['role' => 'receptionist'])] as $user) {
            $this->actingAs($user)->post(route('trainer-assignments.store', $member), ['trainer_profile_id' => $trainer->id, 'activity' => 'gym', 'reason' => 'Unauthorized assignment'])->assertForbidden();
            $this->get(route('classes.create'))->assertForbidden();
        }
        $this->assertDatabaseCount('trainer_assignments', 0);
    }

    public function test_owner_can_delegate_and_remove_specific_receptionist_permissions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $staff = User::factory()->create(['role' => 'receptionist']);
        $payload = ['name' => $staff->name, 'email' => $staff->email, 'role' => 'receptionist', 'is_active' => 1, 'permissions' => ['assignments.manage', 'schedule.manage']];
        $this->actingAs($owner)->put(route('users.update', $staff), $payload)->assertSessionHasNoErrors();
        $member = Member::factory()->create();
        $trainer = TrainerProfile::factory()->create();
        $this->actingAs($staff->fresh())->post(route('trainer-assignments.store', $member), ['trainer_profile_id' => $trainer->id, 'activity' => 'pilates', 'reason' => 'Program Pilates privat'])->assertSessionHasNoErrors();
        $this->get(route('classes.create'))->assertOk();
        $session = TrainingSession::factory()->create();
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertForbidden();
        $this->actingAs($owner)->put(route('users.update', $staff), [...$payload, 'permissions' => []])->assertSessionHasNoErrors();
        $this->actingAs($staff->fresh())->get(route('classes.create'))->assertForbidden();
        $this->post(route('trainer-assignments.store', $member), ['trainer_profile_id' => $trainer->id, 'activity' => 'gym', 'reason' => 'Removed permission'])->assertForbidden();
        $this->actingAs($owner)->put(route('users.update', $staff), [...$payload, 'permissions' => ['users.manage']])->assertSessionHasErrors('permissions.0');
    }

    public function test_booking_is_whatsapp_only_and_personal_sessions_are_private(): void
    {
        config(['gym.booking_whatsapp' => '081234567890']);
        $account = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $account->id]);
        $group = TrainingSession::factory()->create();
        $personal = TrainingSession::factory()->create(['kind' => 'personal', 'capacity' => 1, 'title' => 'Private programme']);
        $this->actingAs($account)->get(route('classes'))->assertOk()->assertSee('https://wa.me/6281234567890', false)->assertDontSee('Private programme');
        $this->get(route('classes.show', $group))->assertOk()->assertSee('Hubungi admin untuk booking')->assertDontSee('Ikut kelas');
        $this->get(route('classes.show', $personal))->assertNotFound();
        $this->post(route('classes.join', $group))->assertForbidden();
        $this->post(route('classes.enroll', $group), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertForbidden();
        $this->assertDatabaseCount('class_enrollments', 0);
        config(['gym.booking_whatsapp' => null]);
        $this->get(route('classes'))->assertOk()->assertSee('Nomor WhatsApp admin belum tersedia');
    }

    public function test_personal_booking_requires_matching_assignment_and_cannot_reassign_open_booking(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $member = Member::factory()->create();
        $plan = MembershipPlan::factory()->create(['plan_type' => 'pt', 'benefits' => ['gym_personal' => 2]]);
        $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
        app(MembershipService::class)->settle($invoice, $owner, 'qris', null);
        $session = TrainingSession::factory()->create(['activity' => 'gym', 'kind' => 'personal', 'capacity' => 1]);
        $booking = ['member_id' => $member->id, 'wa_confirmed' => 1];
        $this->post(route('classes.enroll', $session), $booking)->assertSessionHasErrors('member_id');
        $this->post(route('trainer-assignments.store', $member), ['trainer_profile_id' => $session->trainer_profile_id, 'activity' => 'gym', 'reason' => 'Penugasan program Gym'])->assertSessionHasNoErrors();
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id])->assertSessionHasErrors('wa_confirmed');
        $this->post(route('classes.enroll', $session), $booking)->assertSessionHasNoErrors();
        $this->post(route('classes.enroll', $session), $booking)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_enrollments', 1);
        $this->assertDatabaseHas('class_enrollments', ['member_id' => $member->id, 'booking_source' => 'whatsapp']);
        $assignment = TrainerAssignment::firstOrFail();
        $this->delete(route('trainer-assignments.destroy', $assignment), ['reason' => 'Tidak boleh putus booking'])->assertSessionHasErrors('assignment');
        $this->post(route('trainer-assignments.store', $member), ['trainer_profile_id' => TrainerProfile::factory()->create()->id, 'activity' => 'gym', 'reason' => 'Ganti PT saat masih booking'])->assertSessionHasErrors('trainer_profile_id');
        app(TrainingService::class)->attendance($session->enrollments()->firstOrFail(), 'cancelled', $owner);
        $this->delete(route('trainer-assignments.destroy', $assignment), ['reason' => 'Booking sudah dibatalkan'])->assertSessionHasNoErrors();
    }

    public function test_expired_or_wrong_activity_assignment_cannot_book_personal_session(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $member = Member::factory()->create();
        $session = TrainingSession::factory()->create(['activity' => 'pilates', 'kind' => 'personal', 'capacity' => 1]);
        TrainerAssignment::factory()->create(['member_id' => $member->id, 'trainer_profile_id' => $session->trainer_profile_id, 'activity' => 'gym']);
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        TrainerAssignment::factory()->create(['member_id' => $member->id, 'trainer_profile_id' => $session->trainer_profile_id, 'activity' => 'pilates', 'ends_at' => now()->addHour()]);
        $this->post(route('classes.enroll', $session), ['member_id' => $member->id, 'wa_confirmed' => 1])->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('class_enrollments', 0);
    }

    public function test_editing_profile_without_permission_payload_preserves_removed_booking_permission(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $staff = User::factory()->create(['role' => 'receptionist']);
        $staff->forceFill(['permissions' => []])->save();
        $this->actingAs($owner)->put(route('users.update', $staff), [
            'name' => 'Updated staff', 'email' => $staff->email, 'role' => 'receptionist', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame([], $staff->fresh()->permissions);
        $this->assertFalse($staff->fresh()->can('booking.manage'));
    }

    public function test_assignment_forms_require_login_and_escape_management_reason(): void
    {
        $member = Member::factory()->create();
        $this->post(route('trainer-assignments.store', $member))->assertRedirect(route('login'));
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->post(route('trainer-assignments.store', $member))->assertSessionHasErrors(['trainer_profile_id', 'activity', 'reason']);
        $trainer = TrainerProfile::factory()->create();
        $this->post(route('trainer-assignments.store', $member), [
            'trainer_profile_id' => $trainer->id, 'activity' => 'gym', 'reason' => '<script>alert("coach")</script>',
        ])->assertSessionHasNoErrors();
        $this->get(route('members.show', $member))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("coach")</script>', false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'trainer.assigned']);
    }
}
