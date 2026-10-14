<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OwnerWorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_every_existing_management_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        foreach (['/admin/dashboard', '/frontdesk/checkin', '/members', '/members/new', '/payments', '/shifts', '/admin/staff', '/admin/membership-plans', '/admin/reports', '/admin/reports/export', '/admin/audit'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_owner_inherits_operational_roles_but_inactive_owner_is_blocked(): void
    {
        Route::middleware(['web', 'auth', 'role:personal_trainer'])->get('/test/trainer-workspace', fn () => response('Trainer workspace'));
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->get('/test/trainer-workspace')->assertOk();
        $owner->forceFill(['is_active' => false])->save();
        $this->actingAs($owner->fresh())->get('/test/trainer-workspace')->assertForbidden();
    }

    public function test_member_privacy_and_receptionist_management_limits_remain_enforced(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']))->get('/member')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'member']))->get('/admin/dashboard')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get('/admin/staff')->assertForbidden();
    }
}
