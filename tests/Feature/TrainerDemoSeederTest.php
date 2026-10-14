<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TrainerDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrainerDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_trainers_are_repeatable_and_preserve_profile_and_password(): void
    {
        config(['gym.preview_seed_enabled' => true, 'gym.preview_seed_password' => 'preview-password']);
        $this->seed(TrainerDemoSeeder::class);
        $user = User::where('email', 'trainer1@sparta.test')->firstOrFail();
        $user->update(['password' => 'changed-password']);
        $user->trainerProfile->update(['bio' => 'Custom biography']);
        $this->seed(TrainerDemoSeeder::class);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('trainer_profiles', 3);
        $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));
        $this->assertSame('Custom biography', $user->trainerProfile->fresh()->bio);
        $this->actingAs(User::factory()->create(['role' => 'owner']))->get('/admin/trainers')->assertOk()->assertSee('Demo Nadia Putri')->assertSee('trainer-card');
    }

    public function test_preview_requires_explicit_enablement(): void
    {
        config(['gym.preview_seed_enabled' => false]);
        $this->expectException(\RuntimeException::class);
        $this->seed(TrainerDemoSeeder::class);
    }
}
