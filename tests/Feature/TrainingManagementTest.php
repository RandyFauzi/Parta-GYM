<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\StudioRoom;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(TrainerProfile $trainer, StudioRoom $room): array
    {
        return ['title' => 'Pilates Flow', 'activity' => 'pilates', 'kind' => 'group', 'trainer_profile_id' => $trainer->id, 'studio_room_id' => $room->id, 'starts_at' => now()->addDay()->timezone(config('gym.timezone'))->format('Y-m-d\TH:i'), 'ends_at' => now()->addDay()->addHour()->timezone(config('gym.timezone'))->format('Y-m-d\TH:i'), 'capacity' => 4];
    }

    public function test_owner_schedules_class_and_blocks_trainer_or_room_overlap(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $trainer = TrainerProfile::factory()->create();
        $room = StudioRoom::factory()->create();
        $data = $this->schedule($trainer, $room);
        $this->post('/admin/classes', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('training_sessions', 1);
        $this->post('/admin/classes', [...$data, 'studio_room_id' => StudioRoom::factory()->create()->id])->assertSessionHasErrors('trainer_profile_id');
        $this->post('/admin/classes', [...$data, 'trainer_profile_id' => TrainerProfile::factory()->create()->id])->assertSessionHasErrors('studio_room_id');
        $this->assertDatabaseCount('training_sessions', 1);
        $session = TrainingSession::firstOrFail();
        $this->get('/classes/'.$session->id)->assertOk()->assertSee('Pilates Flow');
        $this->get('/admin/classes/'.$session->id.'/edit')->assertOk();
    }

    public function test_personal_capacity_and_room_capacity_are_validated(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $data = $this->schedule(TrainerProfile::factory()->create(), StudioRoom::factory()->create(['capacity' => 2]));
        $this->post('/admin/classes', $data)->assertSessionHasErrors('capacity');
        $this->post('/admin/classes', [...$data, 'kind' => 'personal', 'capacity' => 2])->assertSessionHasErrors('capacity');
        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_enrollment_is_repeatable_capacity_limited_and_member_private(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'receptionist']));
        $session = TrainingSession::factory()->create(['capacity' => 1]);
        $account = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $account->id, 'full_name' => 'Private Participant']);
        $this->post('/classes/'.$session->id.'/participants', ['member_id' => $member->id])->assertSessionHasNoErrors();
        $this->post('/classes/'.$session->id.'/participants', ['member_id' => $member->id])->assertSessionHasNoErrors();
        $this->post('/classes/'.$session->id.'/participants', ['member_id' => Member::factory()->create()->id])->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('class_enrollments', 1);
        $otherAccount = User::factory()->create(['role' => 'member']);
        Member::factory()->create(['user_id' => $otherAccount->id]);
        $this->actingAs($otherAccount)->get('/classes/'.$session->id)->assertOk()->assertDontSee('Private Participant');
        $this->actingAs($account)->get('/classes/'.$session->id)->assertOk()->assertSee('Private Participant');
        $this->post('/classes/'.$session->id.'/participants', ['member_id' => $member->id])->assertForbidden();
    }

    public function test_trainer_only_sees_assigned_classes_and_records_attendance_once(): void
    {
        $session = TrainingSession::factory()->create(['starts_at' => now()->subMinutes(10), 'ends_at' => now()->addMinutes(50)]);
        $enrollment = ClassEnrollment::factory()->create(['training_session_id' => $session->id]);
        $this->actingAs($session->trainer->user);
        $this->get('/classes/'.$session->id)->assertOk();
        $this->put('/class-participants/'.$enrollment->id, ['status' => 'attended'])->assertSessionHasNoErrors();
        $first = $enrollment->fresh()->attended_at;
        $this->put('/class-participants/'.$enrollment->id, ['status' => 'attended'])->assertSessionHasNoErrors();
        $this->assertTrue($first->equalTo($enrollment->fresh()->attended_at));
        $this->actingAs(TrainerProfile::factory()->create()->user)->get('/classes/'.$session->id)->assertForbidden();
        $this->put('/class-participants/'.$enrollment->id, ['status' => 'attended'])->assertForbidden();
    }

    public function test_cancellation_releases_participants_and_protects_trainer_accounts(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $session = TrainingSession::factory()->create();
        ClassEnrollment::factory()->create(['training_session_id' => $session->id]);
        $trainer = $session->trainer->user;
        $this->delete('/admin/users/'.$trainer->id, ['confirmed' => 1])->assertSessionHasErrors('user');
        $this->post('/admin/classes/'.$session->id.'/close', ['status' => 'cancelled', 'reason' => 'Studio maintenance', 'confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $session->fresh()->status);
        $this->assertDatabaseHas('class_enrollments', ['training_session_id' => $session->id, 'status' => 'cancelled']);
        $this->delete('/admin/users/'.$trainer->id, ['confirmed' => 1])->assertRedirect('/admin/users');
        $this->get('/classes/'.$session->id)->assertOk();
    }

    public function test_rooms_are_owner_only_and_cannot_change_with_upcoming_sessions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $data = ['name' => 'Reformer Studio', 'capacity' => 8, 'is_exclusive' => 1, 'is_active' => 1];
        $this->post('/admin/rooms', $data)->assertSessionHasNoErrors();
        $room = StudioRoom::where('name', 'Reformer Studio')->firstOrFail();
        TrainingSession::factory()->create(['studio_room_id' => $room->id]);
        $this->post('/admin/rooms', [...$data, 'id' => $room->id, 'capacity' => 6])->assertSessionHasErrors('room');
        $this->assertSame(8, $room->fresh()->capacity);
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->post('/admin/rooms', $data)->assertForbidden();
    }

    public function test_shared_room_cannot_exceed_combined_capacity_and_schedule_with_participants_keeps_time(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $room = StudioRoom::factory()->create(['is_exclusive' => false, 'capacity' => 6]);
        $data = $this->schedule(TrainerProfile::factory()->create(), $room);
        $this->post('/admin/classes', $data)->assertSessionHasNoErrors();
        $session = TrainingSession::firstOrFail();
        $this->post('/admin/classes', [...$data, 'trainer_profile_id' => TrainerProfile::factory()->create()->id])->assertSessionHasErrors('capacity');
        ClassEnrollment::factory()->create(['training_session_id' => $session->id]);
        $this->put('/admin/classes/'.$session->id, [...$data, 'starts_at' => now()->addDays(2)->timezone(config('gym.timezone'))->format('Y-m-d\TH:i'), 'ends_at' => now()->addDays(2)->addHour()->timezone(config('gym.timezone'))->format('Y-m-d\TH:i')])->assertSessionHasErrors('starts_at');
        $this->get('/admin/classes/new?source='.$session->id)->assertOk()->assertSee('Pilates Flow');
    }

    public function test_trainer_schedules_only_own_classes_and_member_self_enrolls(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $other = TrainerProfile::factory()->create();
        $data = $this->schedule($other, StudioRoom::factory()->create());
        $this->actingAs($trainer->user)->get('/admin/classes/new')->assertOk();
        $this->post('/admin/classes', $data)->assertSessionHasNoErrors();
        $session = TrainingSession::firstOrFail();
        $this->assertSame($trainer->id, $session->trainer_profile_id);
        $foreign = TrainingSession::factory()->create(['trainer_profile_id' => $other->id]);
        $this->get('/admin/classes/'.$foreign->id.'/edit')->assertForbidden();
        $this->put('/admin/classes/'.$foreign->id, $data)->assertForbidden();
        $this->post('/admin/classes/'.$foreign->id.'/close', ['status' => 'cancelled', 'reason' => 'Wrong class', 'confirmed' => 1])->assertForbidden();
        $account = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $account->id]);
        $this->actingAs($account)->get('/classes/'.$session->id)->assertOk()->assertSee('Ikut kelas');
        $this->post('/classes/'.$session->id.'/join', ['member_id' => Member::factory()->create()->id])->assertSessionHasNoErrors();
        $this->post('/classes/'.$session->id.'/join')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('class_enrollments', 1);
        $this->assertDatabaseHas('class_enrollments', ['member_id' => $member->id, 'training_session_id' => $session->id]);
        $this->get('/classes?mine=1')->assertOk()->assertSee('Pilates Flow');
        $this->get('/admin/classes/new')->assertForbidden();
    }
}
