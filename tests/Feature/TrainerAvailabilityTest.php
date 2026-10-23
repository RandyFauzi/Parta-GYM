<?php

namespace Tests\Feature;

use App\Models\StudioRoom;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function schedule(TrainerProfile $trainer, string $start, string $end): array
    {
        return ['title' => 'Available session', 'activity' => 'gym', 'kind' => 'personal', 'capacity' => 1,
            'trainer_profile_id' => $trainer->id, 'studio_room_id' => StudioRoom::factory()->create()->id, 'starts_at' => $start, 'ends_at' => $end];
    }

    public function test_weekly_availability_buffer_and_time_off_are_enforced_when_scheduling(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $trainer = TrainerProfile::factory()->create();
        $this->put(route('trainer-availability.update', $trainer), ['availability' => ['1' => [['from' => '09:00', 'to' => '17:00']]], 'session_buffer_minutes' => 15])->assertSessionHasNoErrors();
        $data = $this->schedule($trainer, '2026-10-26T09:00', '2026-10-26T10:00');
        $this->post(route('classes.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('classes.store'), [...$data, 'starts_at' => '2026-10-26T10:05', 'ends_at' => '2026-10-26T11:00'])->assertSessionHasErrors('trainer_profile_id');
        $this->post(route('classes.store'), [...$data, 'starts_at' => '2026-10-26T08:30', 'ends_at' => '2026-10-26T09:00'])->assertSessionHasErrors('trainer_profile_id');
        $this->post(route('trainer-time-off.store', $trainer), ['starts_at' => '2026-10-26T12:00', 'ends_at' => '2026-10-26T13:00', 'reason' => 'Keperluan sertifikasi'])->assertSessionHasNoErrors();
        $this->post(route('classes.store'), [...$data, 'starts_at' => '2026-10-26T12:00', 'ends_at' => '2026-10-26T13:00'])->assertSessionHasErrors('trainer_profile_id');
        $this->post(route('classes.store'), [...$data, 'starts_at' => '2026-10-26T10:15', 'ends_at' => '2026-10-26T11:00'])->assertSessionHasNoErrors();
        $this->get(route('trainer-availability', ['trainer_id' => $trainer->id, 'date' => '2026-10-26']))->assertOk()->assertSee('Keperluan sertifikasi')->assertDontSee('11:30–12:30');
        $this->assertDatabaseCount('training_sessions', 2);
    }

    public function test_cannot_restrict_hours_or_add_time_off_over_existing_scheduled_sessions(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $trainer = TrainerProfile::factory()->create();
        $session = TrainingSession::factory()->create(['trainer_profile_id' => $trainer->id, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $this->put(route('trainer-availability.update', $trainer), ['availability' => [], 'session_buffer_minutes' => 0])->assertSessionHasErrors('trainer_profile_id');
        $this->assertNull($trainer->fresh()->availability);
        $this->post(route('trainer-time-off.store', $trainer), ['starts_at' => '2026-10-26T09:00', 'ends_at' => '2026-10-26T10:00', 'reason' => 'Bentrok jadwal yang ada'])->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('trainer_time_offs', 0);
        $this->assertSame('scheduled', $session->fresh()->status);
    }

    public function test_overnight_hours_work_and_overlapping_intervals_are_rejected(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $trainer = TrainerProfile::factory()->create();
        $this->put(route('trainer-availability.update', $trainer), ['availability' => ['1' => [['from' => '22:00', 'to' => '02:00']]], 'session_buffer_minutes' => 0])->assertSessionHasNoErrors();
        $this->post(route('classes.store'), $this->schedule($trainer, '2026-10-27T00:30', '2026-10-27T01:30'))->assertSessionHasNoErrors();
        $this->put(route('trainer-availability.update', $trainer), ['availability' => ['1' => [['from' => '22:00', 'to' => '02:00']], '2' => [['from' => '01:00', 'to' => '03:00']]], 'session_buffer_minutes' => 0])->assertSessionHasErrors('availability');
    }

    public function test_trainer_only_reads_own_availability_and_management_permission_is_required_for_changes(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $other = TrainerProfile::factory()->create();
        $this->actingAs($trainer->user)->get(route('trainer-availability'))->assertOk()->assertSee($trainer->user->name)->assertDontSee($other->user->name);
        $this->get(route('trainer-availability', ['trainer_id' => $other->id]))->assertForbidden();
        $this->put(route('trainer-availability.update', $trainer), ['availability' => [], 'session_buffer_minutes' => 0])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get(route('trainer-availability'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('trainer-availability'))->assertForbidden();
    }
}
