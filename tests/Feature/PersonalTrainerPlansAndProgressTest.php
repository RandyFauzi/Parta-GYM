<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalTrainerPlansAndProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_pt_plans_with_duration_and_session_limits(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);

        // 1. PT Full 30 Hari: Rp 700.000
        $response1 = $this->post(route('plans.store'), [
            'name' => 'PT Full 30 Hari',
            'plan_type' => 'pt',
            'duration_unit' => 'month',
            'duration_value' => 1,
            'price_idr' => 700000,
            'visit_limit' => null,
            'description' => '30 hari full PT',
            'is_active' => 1,
        ]);
        $response1->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_plans', [
            'name' => 'PT Full 30 Hari',
            'plan_type' => 'pt',
            'price_idr' => 700000,
            'visit_limit' => null,
        ]);

        // 2. PT 15 Sesi: Rp 500.000
        $response2 = $this->post(route('plans.store'), [
            'name' => 'PT 15 Sesi',
            'plan_type' => 'pt',
            'duration_unit' => 'month',
            'duration_value' => 2,
            'price_idr' => 500000,
            'visit_limit' => 15,
            'description' => '15 sesi PT',
            'is_active' => 1,
        ]);
        $response2->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_plans', [
            'name' => 'PT 15 Sesi',
            'plan_type' => 'pt',
            'price_idr' => 500000,
            'visit_limit' => 15,
        ]);

        // 3. PT 10 Sesi: Rp 250.000
        $response3 = $this->post(route('plans.store'), [
            'name' => 'PT 10 Sesi',
            'plan_type' => 'pt',
            'duration_unit' => 'month',
            'duration_value' => 1,
            'price_idr' => 250000,
            'visit_limit' => 10,
            'description' => '10 sesi PT',
            'is_active' => 1,
        ]);
        $response3->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_plans', [
            'name' => 'PT 10 Sesi',
            'plan_type' => 'pt',
            'price_idr' => 250000,
            'visit_limit' => 10,
        ]);
    }

    public function test_personal_trainer_can_log_member_progress_with_metrics_and_notes(): void
    {
        $trainerUser = User::factory()->create(['role' => 'personal_trainer']);
        TrainerProfile::create(['user_id' => $trainerUser->id, 'bio' => 'Coach Pro', 'is_bookable' => true]);

        $member = Member::factory()->create(['full_name' => 'Budi Santoso']);
        $session = TrainingSession::factory()->create(['trainer_profile_id' => $trainerUser->trainerProfile->id]);
        ClassEnrollment::factory()->create(['training_session_id' => $session->id, 'member_id' => $member->id]);

        $this->actingAs($trainerUser);

        $response = $this->post("/admin/members/{$member->id}/progress", [
            'recorded_at' => CarbonImmutable::now()->format('Y-m-d H:i:s'),
            'weight_kg' => 75.5,
            'height_cm' => 175.0,
            'body_fat_percentage' => 19.2,
            'muscle_mass_kg' => 34.0,
            'chest_cm' => 102.0,
            'waist_cm' => 84.0,
            'workout_summary' => "Upper Body Focus:\n- Incline Bench Press 4x10 (50kg)\n- Lat Pulldown 4x12 (45kg)",
            'diet_summary' => 'Tingkatkan asupan protein harian minimal 120g.',
            'coach_notes' => 'Postur bench press sudah membaik, jaga retractor scapula.',
            'next_goals' => 'Target Bench Press 55kg minggu depan.',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $record = MemberProgressRecord::where('member_id', $member->id)->firstOrFail();
        $this->assertSame($trainerUser->id, $record->trainer_id);
        $this->assertEquals(75.5, $record->weight_kg);
        $this->assertEquals(175.0, $record->height_cm);
        $this->assertEquals(24.7, $record->bmi);
        $this->assertSame('Ideal (Normal)', $record->bmi_category);
        $this->assertStringContainsString('Upper Body Focus', $record->workout_summary);
    }

    public function test_member_can_view_their_progress_in_portal(): void
    {
        $memberUser = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $memberUser->id]);

        $trainerUser = User::factory()->create(['role' => 'personal_trainer', 'name' => 'Coach Dian']);
        TrainerProfile::create(['user_id' => $trainerUser->id, 'bio' => 'Coach Dian', 'is_bookable' => true]);

        MemberProgressRecord::create([
            'member_id' => $member->id,
            'trainer_id' => $trainerUser->id,
            'recorded_at' => now(),
            'weight_kg' => 70.0,
            'height_cm' => 170.0,
            'workout_summary' => 'Sesi kardio HIIT 30 menit',
            'coach_notes' => 'Stamina sangat bagus hari ini!',
        ]);

        $this->actingAs($memberUser);

        $response = $this->get('/member/progress');
        $response->assertOk();
        $response->assertSee('Progres PT');
        $response->assertSee('Sesi kardio HIIT 30 menit');
        $response->assertSee('Coach Dian');
        $response->assertSee('70 kg');
    }

    public function test_member_cannot_log_progress_records(): void
    {
        $memberUser = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $memberUser->id]);

        $this->actingAs($memberUser);

        $response = $this->post("/admin/members/{$member->id}/progress", [
            'recorded_at' => now()->format('Y-m-d H:i:s'),
            'workout_summary' => 'Mencoba submit sendiri',
        ]);

        $response->assertForbidden();
    }
}
