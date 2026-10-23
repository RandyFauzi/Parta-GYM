<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Models\TrainerAssignment;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MemberProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgramProgressTest extends TestCase
{
    use RefreshDatabase;

    private function assignment(Member $member, string $activity = 'gym'): TrainerAssignment
    {
        return TrainerAssignment::factory()->create(['member_id' => $member->id, 'activity' => $activity]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['recorded_at' => now()->timezone(config('gym.timezone'))->format('Y-m-d H:i:s'), 'workout_summary' => 'Kontrol gerakan meningkat.', 'activity' => 'gym', 'program_name' => 'Kekuatan minggu 4', 'request_key' => (string) Str::uuid(), 'exercises' => [['name' => 'Squat', 'sets' => 3, 'reps' => 12, 'load_kg' => 0]]], $overrides);
    }

    public function test_assigned_trainer_records_program_once_and_member_reads_details(): void
    {
        $this->freezeTime();
        $account = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $account->id]);
        $assignment = $this->assignment($member);
        $trainer = $assignment->trainer->user;
        $payload = $this->payload(['trainer_id' => 999, 'member_id' => (string) Str::uuid()]);
        $this->actingAs($trainer)->get(route('coaching.member', $member))->assertOk()->assertSee('Nama program');
        $this->post(route('members.progress.store', $member), $payload)->assertSessionHasNoErrors();
        $this->post(route('members.progress.store', $member), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('member_progress_records', 1);
        $record = MemberProgressRecord::firstOrFail();
        $this->assertSame($trainer->id, $record->trainer_id);
        $this->assertSame($member->id, $record->member_id);
        $this->assertTrue($record->recorded_at->equalTo(now()->startOfSecond()));
        $this->assertSame('Squat', $record->exercises[0]['name']);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->actingAs($account)->get(route('portal.progress'))->assertOk()->assertSee('Kekuatan minggu 4')->assertSee('Squat');
        $this->get(route('members.progress.show', [$member, $record]))->assertOk()->assertSee('Squat');
    }

    public function test_activity_assignment_limits_list_detail_create_and_delete(): void
    {
        $member = Member::factory()->create();
        $assignment = $this->assignment($member);
        $trainer = $assignment->trainer->user;
        $gym = MemberProgressRecord::factory()->create(['member_id' => $member->id, 'activity' => 'gym', 'workout_summary' => 'Gym visible']);
        $pilates = MemberProgressRecord::factory()->create(['member_id' => $member->id, 'activity' => 'pilates', 'trainer_id' => $trainer->id, 'workout_summary' => 'Pilates private']);
        $this->actingAs($trainer)->get(route('coaching.member', $member))->assertOk()->assertSee('Gym visible')->assertDontSee('Pilates private');
        $this->get(route('members.progress.show', [$member, $pilates]))->assertForbidden();
        $this->delete(route('members.progress.destroy', $pilates))->assertForbidden();
        $this->delete(route('members.progress.destroy', $gym))->assertForbidden();
        $this->post(route('members.progress.store', $member), $this->payload(['activity' => 'pilates']))->assertForbidden();
        $assignment->update(['revoked_at' => now(), 'active_key' => null]);
        $this->get(route('coaching.member', $member))->assertForbidden();
        $this->get(route('members.progress.show', [$member, $gym]))->assertForbidden();
        $this->post(route('members.progress.store', $member), $this->payload())->assertForbidden();
    }

    public function test_expired_future_and_inactive_assignments_do_not_allow_reporting(): void
    {
        $member = Member::factory()->create();
        $assignment = $this->assignment($member);
        $this->actingAs($assignment->trainer->user);
        $assignment->update(['ends_at' => now()->subSecond()]);
        $this->post(route('members.progress.store', $member), $this->payload())->assertForbidden();
        $assignment->update(['ends_at' => null, 'starts_at' => now()->addDay()]);
        $this->post(route('members.progress.store', $member), $this->payload())->assertForbidden();
        $assignment->update(['starts_at' => now()->subDay()]);
        $assignment->trainer->user->forceFill(['is_active' => false])->save();
        $this->post(route('members.progress.store', $member), $this->payload())->assertForbidden();
    }

    public function test_members_only_read_their_own_history_after_trainer_replacement(): void
    {
        $member = Member::factory()->create(['user_id' => User::factory()->create(['role' => 'member'])->id]);
        $assignment = $this->assignment($member);
        $record = MemberProgressRecord::factory()->create(['member_id' => $member->id, 'activity' => 'gym']);
        $assignment->update(['revoked_at' => now(), 'active_key' => null]);
        $replacement = $this->assignment($member);
        $this->actingAs($replacement->trainer->user)->get(route('members.progress.show', [$member, $record]))->assertOk();
        $this->actingAs(User::findOrFail($member->user_id))->get(route('members.progress.show', [$member, $record]))->assertOk();
        $this->post(route('members.progress.store', $member), $this->payload())->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('members.progress.show', [$member, $record]))->assertForbidden();
        $other = Member::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'owner']))->get(route('members.progress.show', [$other, $record]))->assertNotFound();
    }

    public function test_receptionist_cannot_see_sensitive_progress_on_member_page_or_detail(): void
    {
        $member = Member::factory()->create();
        $record = MemberProgressRecord::factory()->create(['member_id' => $member->id, 'workout_summary' => 'Sensitive coaching report']);
        $this->actingAs(User::factory()->create(['role' => 'receptionist']));
        $this->get(route('members.show', $member))->assertOk()->assertDontSee('Sensitive coaching report');
        $this->get(route('members.progress.show', [$member, $record]))->assertForbidden();
        $this->post(route('members.progress.store', $member), $this->payload())->assertForbidden();
    }

    public function test_session_must_belong_to_member_trainer_and_activity_and_have_started(): void
    {
        $member = Member::factory()->create();
        $assignment = $this->assignment($member);
        $session = TrainingSession::factory()->create(['trainer_profile_id' => $assignment->trainer_profile_id, 'activity' => 'gym']);
        ClassEnrollment::factory()->create(['member_id' => $member->id, 'training_session_id' => $session->id]);
        $this->actingAs($assignment->trainer->user);
        $this->post(route('members.progress.store', $member), $this->payload(['training_session_id' => $session->id]))->assertSessionHasErrors('training_session_id');
        $session->update(['starts_at' => now()->subHour(), 'ends_at' => now()]);
        $this->post(route('members.progress.store', $member), $this->payload(['training_session_id' => $session->id]))->assertSessionHasNoErrors();
        $session->update(['activity' => 'pilates']);
        $this->post(route('members.progress.store', $member), $this->payload(['training_session_id' => $session->id]))->assertSessionHasErrors('training_session_id');
        $session->update(['activity' => 'gym', 'trainer_profile_id' => TrainerProfile::factory()->create()->id]);
        $this->post(route('members.progress.store', $member), $this->payload(['training_session_id' => $session->id]))->assertForbidden();
        $session->update(['trainer_profile_id' => $assignment->trainer_profile_id, 'status' => 'cancelled']);
        $this->post(route('members.progress.store', $member), $this->payload(['training_session_id' => $session->id]))->assertSessionHasErrors('training_session_id');
        $this->post(route('members.progress.store', $member), $this->payload(['training_session_id' => TrainingSession::factory()->create()->id]))->assertSessionHasErrors('training_session_id');
    }

    public function test_validation_rejects_bad_metrics_exercises_and_future_report(): void
    {
        $member = Member::factory()->create();
        $assignment = $this->assignment($member);
        $this->actingAs($assignment->trainer->user);
        $this->post(route('members.progress.store', $member), $this->payload(['recorded_at' => now()->addDays(2)->toDateTimeString(), 'weight_kg' => -1, 'exercises' => [['name' => '', 'sets' => -1, 'load_kg' => -1]]]))->assertSessionHasErrors(['recorded_at', 'weight_kg', 'exercises.0.name', 'exercises.0.sets', 'exercises.0.load_kg']);
        $this->post(route('members.progress.store', $member), $this->payload(['exercises' => array_fill(0, 21, ['name' => 'Too many'])]))->assertSessionHasErrors('exercises');
        $this->assertDatabaseCount('member_progress_records', 0);
        $this->post(route('members.progress.store', $member), $this->payload(['exercises' => [['name' => '', 'sets' => null]]]))->assertSessionHasNoErrors();
        $this->assertSame([], MemberProgressRecord::firstOrFail()->exercises);
    }

    public function test_summary_uses_non_null_measurements_and_is_independent_of_pagination(): void
    {
        $member = Member::factory()->create(['user_id' => User::factory()->create(['role' => 'member'])->id]);
        MemberProgressRecord::factory()->create(['member_id' => $member->id, 'recorded_at' => now()->subDays(20), 'weight_kg' => 80, 'waist_cm' => 90]);
        MemberProgressRecord::factory()->count(11)->create(['member_id' => $member->id, 'recorded_at' => now()->subDays(10)]);
        MemberProgressRecord::factory()->create(['member_id' => $member->id, 'recorded_at' => now()->subDay(), 'weight_kg' => 75]);
        MemberProgressRecord::factory()->create(['member_id' => $member->id, 'recorded_at' => now(), 'workout_summary' => 'Latest report']);
        $summary = app(MemberProgressService::class)->measurements($member->progressRecords());
        $this->assertSame(-5.0, $summary['weight_kg']['change']);
        $this->assertSame(90.0, $summary['waist_cm']['latest']);
        $this->actingAs(User::findOrFail($member->user_id))->get(route('portal.progress', ['page' => 2]))->assertOk()->assertViewHas('latest', fn ($latest) => $latest->workout_summary === 'Latest report')->assertViewHas('measurements', fn ($metrics) => $metrics['weight_kg']['latest'] === 75.0);
    }

    public function test_legacy_session_activity_is_respected_without_rewriting_old_records(): void
    {
        $member = Member::factory()->create();
        $assignment = $this->assignment($member);
        $session = TrainingSession::factory()->create(['activity' => 'pilates']);
        $record = MemberProgressRecord::factory()->create(['member_id' => $member->id, 'training_session_id' => $session->id]);
        $this->actingAs($assignment->trainer->user)->get(route('members.progress.show', [$member, $record]))->assertForbidden();
        $this->assertNull($record->fresh()->activity);
    }

    public function test_owner_can_delete_with_snapshot_audit_and_trainer_can_delete_only_own_report(): void
    {
        $member = Member::factory()->create();
        $assignment = $this->assignment($member);
        $record = MemberProgressRecord::factory()->create(['member_id' => $member->id, 'trainer_id' => $assignment->trainer->user_id, 'activity' => 'gym']);
        $this->actingAs($assignment->trainer->user)->delete(route('members.progress.destroy', $record))->assertRedirect();
        $this->assertDatabaseMissing('member_progress_records', ['id' => $record->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_progress.deleted', 'entity_id' => $record->id]);
        $record = MemberProgressRecord::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'owner']))->delete(route('members.progress.destroy', $record))->assertRedirect();
    }

    public function test_member_activity_filter_preserves_legacy_history_and_scopes_measurements(): void
    {
        $account = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $account->id]);
        MemberProgressRecord::factory()->create(['member_id' => $member->id, 'activity' => 'gym', 'weight_kg' => 80, 'workout_summary' => 'Gym report']);
        MemberProgressRecord::factory()->create(['member_id' => $member->id, 'activity' => 'pilates', 'weight_kg' => 75, 'workout_summary' => 'Pilates report']);
        MemberProgressRecord::factory()->create(['member_id' => $member->id, 'workout_summary' => 'Legacy report']);
        $this->actingAs($account)->get(route('portal.progress'))->assertOk()->assertSee('Legacy report');
        $this->get(route('portal.progress', ['activity' => 'gym']))->assertOk()->assertSee('Gym report')->assertDontSee('Pilates report')->assertDontSee('Legacy report')->assertViewHas('measurements', fn ($summary) => $summary['weight_kg']['latest'] === 80.0);
        $this->get(route('portal.progress', ['activity' => 'invalid']))->assertSessionHasErrors('activity');
    }

    public function test_reused_request_key_cannot_link_or_expose_another_members_report(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $first = Member::factory()->create();
        $second = Member::factory()->create();
        foreach ([$first, $second] as $member) {
            TrainerAssignment::factory()->create(['member_id' => $member->id, 'trainer_profile_id' => $trainer->id, 'activity' => 'gym']);
        }
        $payload = $this->payload();
        $this->actingAs($trainer->user)->post(route('members.progress.store', $first), $payload)->assertSessionHasNoErrors();
        $this->post(route('members.progress.store', $second), $payload)->assertForbidden();
        $this->assertDatabaseCount('member_progress_records', 1);
    }

    public function test_migration_preserves_all_legacy_columns(): void
    {
        $record = MemberProgressRecord::factory()->create(['weight_kg' => 78.5, 'coach_notes' => 'Historical notes']);
        $migration = require database_path('migrations/2026_10_23_102018_add_program_details_to_member_progress_records.php');
        $migration->down();
        $original = DB::table('member_progress_records')->where('id', $record->id)->first();
        $migration->up();
        $migrated = (array) DB::table('member_progress_records')->where('id', $record->id)->first();
        foreach ((array) $original as $key => $value) {
            $this->assertSame($value, $migrated[$key]);
        }
        foreach (['activity', 'program_name', 'exercises', 'request_key'] as $key) {
            $this->assertNull($migrated[$key]);
        }
    }
}
