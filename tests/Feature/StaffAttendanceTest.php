<?php

namespace Tests\Feature;

use App\Models\StaffAttendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_clock_in_and_clock_out_successfully(): void
    {
        Storage::fake('attendance');
        $trainer = User::factory()->create([
            'role' => 'personal_trainer',
            'name' => 'Coach Budi',
        ]);

        $this->actingAs($trainer);

        // 1. Clock In
        $clockInResponse = $this->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('in.jpg', 640, 480)]);
        $clockInResponse->assertRedirect()->assertSessionHas('success');

        $tz = config('gym.timezone', 'Asia/Makassar');
        $today = CarbonImmutable::now($tz)->toDateString();

        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $trainer->id,
            'date' => $today,
            'status' => StaffAttendance::STATUS_PRESENT,
        ]);

        $attendance = StaffAttendance::where('user_id', $trainer->id)->where('date', $today)->first();
        $this->assertNotNull($attendance->clock_in_at);
        $this->assertNull($attendance->clock_out_at);

        // 2. Prevent duplicate clock in
        $dupResponse = $this->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('in.jpg', 640, 480)]);
        $dupResponse->assertRedirect()->assertSessionHas('error');

        // 3. Clock Out
        $clockOutResponse = $this->post(route('staff.clock-out'), ['photo' => UploadedFile::fake()->image('out.jpg', 640, 480)]);
        $clockOutResponse->assertRedirect()->assertSessionHas('success');

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out_at);
        $this->assertNotNull($attendance->work_duration_human);

        // 4. Prevent duplicate clock out
        $dupOut = $this->post(route('staff.clock-out'), ['photo' => UploadedFile::fake()->image('out.jpg', 640, 480)]);
        $dupOut->assertRedirect()->assertSessionHas('error');
    }

    public function test_staff_can_view_their_own_attendance_history(): void
    {
        $receptionist = User::factory()->create([
            'role' => 'receptionist',
            'name' => 'Siti Reception',
        ]);

        $this->actingAs($receptionist);

        // Make an attendance record
        $tz = config('gym.timezone', 'Asia/Makassar');
        $today = CarbonImmutable::now($tz)->toDateString();

        StaffAttendance::create([
            'user_id' => $receptionist->id,
            'date' => $today,
            'clock_in_at' => now()->subHours(4),
            'clock_out_at' => now(),
            'status' => StaffAttendance::STATUS_PRESENT,
            'notes' => 'Shift pagi lancar',
            'recorded_by' => $receptionist->id,
        ]);

        $response = $this->get(route('staff.attendance.me'));
        $response->assertOk();
        $response->assertSee('Siti Reception');
        $response->assertSee('Shift pagi lancar');
    }

    public function test_owner_can_view_recap_and_manually_record_staff_attendance(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $trainer = User::factory()->create([
            'role' => 'personal_trainer',
            'name' => 'Coach Andi',
        ]);

        $this->actingAs($owner);

        // 1. View staff attendance dashboard
        $viewResponse = $this->get(route('staff.attendance.index'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Absensi Karyawan &amp; PT', false);

        // 2. Owner manually records sick/permit for coach Andi
        $tz = config('gym.timezone', 'Asia/Makassar');
        $targetDate = CarbonImmutable::now($tz)->subDays(1)->toDateString();

        $manualResponse = $this->post(route('staff.attendance.manual'), [
            'user_id' => $trainer->id,
            'date' => $targetDate,
            'status' => 'sick',
            'clock_in_at' => null,
            'clock_out_at' => null,
            'notes' => 'Izin sakit demam berdarah',
        ]);

        $manualResponse->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $trainer->id,
            'date' => $targetDate,
            'status' => 'sick',
            'notes' => 'Izin sakit demam berdarah',
            'recorded_by' => $owner->id,
        ]);

        // 3. Owner can delete or adjust record
        $record = StaffAttendance::where('user_id', $trainer->id)->where('date', $targetDate)->first();
        $deleteResponse = $this->delete(route('staff.attendance.destroy', $record));
        $deleteResponse->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseMissing('staff_attendances', [
            'id' => $record->id,
        ]);
    }

    public function test_regular_member_cannot_access_staff_attendance_features(): void
    {
        $memberUser = User::factory()->create(['role' => 'member']);
        $this->actingAs($memberUser);

        // Clock in rejected
        $this->post(route('staff.clock-in'), ['photo' => UploadedFile::fake()->image('in.jpg', 640, 480)])->assertForbidden();

        // Admin recap rejected
        $this->get(route('staff.attendance.index'))->assertForbidden();
    }
}
