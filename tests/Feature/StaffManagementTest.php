<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_disable_staff_without_role_injection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->post('/admin/staff', ['name' => 'New Staff', 'email' => 'staff@gym.test', 'password' => 'secure-pass-123', 'password_confirmation' => 'secure-pass-123', 'role' => 'owner'])->assertRedirect();
        $staff = User::where('email', 'staff@gym.test')->firstOrFail();
        $this->assertSame('receptionist', $staff->role);
        $this->post('/admin/staff/'.$staff->id.'/toggle')->assertRedirect();
        $this->actingAs($staff->fresh())->get('/members')->assertForbidden();
        $this->actingAs($owner)->post('/admin/staff/'.$owner->id.'/toggle')->assertForbidden();
    }

    public function test_access_suspension_requires_reason_and_is_owner_only(): void
    {
        $member = Member::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->post('/admin/members/'.$member->id.'/status', ['status' => 'suspended', 'reason' => 'test reason'])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'owner']))->post('/admin/members/'.$member->id.'/status', ['status' => 'suspended'])->assertSessionHasErrors('reason');
        $this->post('/admin/members/'.$member->id.'/status', ['status' => 'suspended', 'reason' => 'Safety review'])->assertRedirect();
        $this->assertSame('suspended', $member->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.access_changed']);
        $this->get('/admin/staff')->assertOk();
    }

    public function test_member_content_is_escaped_and_sensitive_routes_have_private_headers(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $member = Member::factory()->create(['full_name' => '<script>alert(1)</script>']);
        $this->actingAs($owner)->get('/members/'.$member->id)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/admin/reports?from=2026-01-02&to=2026-01-01')->assertSessionHasErrors('to');
    }
}
