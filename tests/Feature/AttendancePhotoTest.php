<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendancePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_clock_in_and_out_store_private_stamped_photos_with_server_times(): void
    {
        Storage::fake('attendance');
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $user = User::factory()->create(['role' => 'personal_trainer']);
        $this->actingAs($user)->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('selfie.png', 640, 480), 'clock_in_at' => '2000-01-01', 'user_id' => 999])->assertSessionHas('success');
        $record = StaffAttendance::firstOrFail();
        $this->assertSame($user->id, $record->user_id);
        $this->assertTrue($record->clock_in_at->equalTo(now()));
        $this->assertTrue($record->clock_in_evidence_at->equalTo(now()));
        Storage::disk('attendance')->assertExists($record->clock_in_photo_path);
        $bytes = Storage::disk('attendance')->get($record->clock_in_photo_path);
        $this->assertSame(hash('sha256', $bytes), $record->clock_in_photo_sha256);
        $this->assertSame(536, getimagesizefromstring($bytes)[1]);
        $this->get(route('staff.attendance.photo', [$record, 'clock_in']))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->travel(8)->hours();
        $this->post(route('staff.clock-out'), ['photo' => UploadedFile::fake()->image('out.jpg', 640, 480)])->assertSessionHas('success');
        $this->assertTrue($record->fresh()->clock_out_evidence_at->equalTo(now()));
        $this->assertCount(2, Storage::disk('attendance')->allFiles());
        $this->get(route('staff.attendance.me'))->assertOk()->assertSee('Foto masuk')->assertSee('Foto pulang')->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
    }

    public function test_photo_is_required_and_invalid_images_are_rejected_without_creating_attendance(): void
    {
        Storage::fake('attendance');
        $this->actingAs(User::factory()->create(['role' => 'receptionist']));
        $this->post(route('staff.clock-in'))->assertSessionHasErrors('photo');
        $this->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->create('fake.jpg', 10, 'text/plain')])->assertSessionHasErrors('photo');
        $this->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('huge.jpg', 5000, 100)])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('staff_attendances', 0);
        $this->assertCount(0, Storage::disk('attendance')->allFiles());
    }

    public function test_replay_does_not_duplicate_files_and_other_staff_cannot_read_evidence(): void
    {
        Storage::fake('attendance');
        $user = User::factory()->create(['role' => 'receptionist']);
        $this->actingAs($user)->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('a.jpg', 640, 480)])->assertSessionHas('success');
        $record = StaffAttendance::firstOrFail();
        $this->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('b.jpg', 640, 480)])->assertSessionHas('error');
        $this->assertCount(1, Storage::disk('attendance')->allFiles());
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get(route('staff.attendance.photo', [$record, 'clock_in']))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'member']))->get(route('staff.attendance.photo', [$record, 'clock_in']))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'owner']))->get(route('staff.attendance.photo', [$record, 'clock_in']))->assertOk();
        $this->delete(route('staff.attendance.destroy', $record))->assertSessionHasErrors('attendance');
        $this->assertDatabaseHas('staff_attendances', ['id' => $record->id]);
    }

    public function test_manual_correction_keeps_original_photo_evidence_and_audits_before_and_after(): void
    {
        Storage::fake('attendance');
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $user = User::factory()->create(['role' => 'personal_trainer']);
        $this->actingAs($user)->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('a.jpg', 640, 480)])->assertSessionHas('success');
        $record = StaffAttendance::firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'owner']))->post(route('staff.attendance.manual'), [
            'user_id' => $user->id, 'date' => '2026-10-26', 'status' => 'present', 'clock_in_time' => '07:30', 'notes' => 'Koreksi jam oleh management',
        ])->assertSessionHasNoErrors();
        $this->assertSame('07:30', $record->fresh()->clock_in_at->timezone(config('gym.timezone'))->format('H:i'));
        $this->assertSame('08:00', $record->fresh()->clock_in_evidence_at->timezone(config('gym.timezone'))->format('H:i'));
        $this->assertSame($record->clock_in_photo_sha256, $record->fresh()->clock_in_photo_sha256);
        $this->assertDatabaseHas('audit_logs', ['action' => 'staff.attendance_manual']);
        $metadata = json_decode(DB::table('audit_logs')->where('action', 'staff.attendance_manual')->value('metadata'), true);
        $this->assertSame('Koreksi jam oleh management', $metadata['after']['notes']);
        $this->assertNotSame($metadata['before']['clock_in_at'], $metadata['after']['clock_in_at']);
    }
}
