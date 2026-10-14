<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ServerPreviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ServerPreviewSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_seed_is_repeatable_and_preserves_account_changes(): void
    {
        config(['gym.preview_seed_enabled' => true, 'gym.preview_seed_password' => 'password']);
        $this->seed(ServerPreviewSeeder::class);
        $owner = User::where('email', 'randyfauzi24@gmail.com')->firstOrFail();
        $this->assertTrue(Hash::check('password', $owner->password));
        $owner->update(['password' => 'changed-password']);
        $this->seed(ServerPreviewSeeder::class);
        $this->assertTrue(Hash::check('changed-password', $owner->fresh()->password));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('members', 6);
        $this->assertDatabaseCount('membership_plans', 4);
        $this->assertDatabaseCount('invoices', 6);
        $this->assertDatabaseCount('payments', 5);
        $this->assertDatabaseCount('checkins', 3);
        $member = User::where('email', 'member1@gmail.com')->firstOrFail();
        $this->actingAs($member)->get('/member')->assertOk();
        $this->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($owner)->get('/admin/dashboard')->assertOk();
    }

    public function test_preview_seed_requires_explicit_enablement(): void
    {
        config(['gym.preview_seed_enabled' => false, 'gym.preview_seed_password' => 'password']);
        $this->expectException(RuntimeException::class);
        $this->seed(ServerPreviewSeeder::class);
    }
}
