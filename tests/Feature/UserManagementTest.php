<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function data(string $role = 'receptionist'): array
    {
        return ['name' => 'New Account', 'email' => 'newaccount@example.com', 'role' => $role, 'is_active' => 1, 'password' => 'private-pass-123', 'password_confirmation' => 'private-pass-123'];
    }

    public function test_only_owner_can_manage_accounts(): void
    {
        foreach (['member', 'receptionist', 'personal_trainer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/users')->assertForbidden();
            $this->post('/admin/users', $this->data())->assertForbidden();
        }
    }

    public function test_owner_creates_edits_archives_and_restores_without_losing_user_row(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $this->post('/admin/users', $this->data())->assertSessionHasNoErrors();
        $user = User::where('email', 'newaccount@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('private-pass-123', $user->password));
        $this->put('/admin/users/'.$user->id, ['name' => 'Updated Name', 'email' => $user->email, 'role' => 'receptionist', 'is_active' => 0])->assertSessionHasNoErrors();
        $this->assertSame('Updated Name', $user->fresh()->name);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertTrue(Hash::check('private-pass-123', $user->fresh()->password));
        $this->delete('/admin/users/'.$user->id, ['confirmed' => 1])->assertRedirect('/admin/users');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->get('/admin/users?status=archived')->assertSee('Updated Name');
        $this->post('/admin/users/'.$user->id.'/restore', ['confirmed' => 1])->assertRedirect('/admin/users/'.$user->id.'/edit');
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_owner_self_access_cannot_be_disabled_demoted_or_deleted(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $this->put('/admin/users/'.$owner->id, ['name' => $owner->name, 'email' => $owner->email, 'role' => 'receptionist', 'is_active' => 1])->assertSessionHasErrors('role');
        $this->delete('/admin/users/'.$owner->id, ['confirmed' => 1])->assertForbidden();
        $this->assertSame('owner', $owner->fresh()->role);
    }

    public function test_member_accounts_require_exclusive_member_link_and_reset_revokes_sessions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $member = Member::factory()->create();
        $data = $this->data('member') + ['member_id' => $member->id];
        $this->post('/admin/users', $data)->assertSessionHasNoErrors();
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame($user->id, $member->fresh()->user_id);
        $this->post('/admin/users', [...$data, 'email' => 'second@example.com'])->assertSessionHasErrors('member_id');
        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->put('/admin/users/'.$user->id, [...$data, 'password' => 'reset-password-123', 'password_confirmation' => 'reset-password-123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('reset-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->actingAs($user->fresh())->get('/member')->assertOk();
    }

    public function test_trainer_uses_same_account_profile_and_own_schedule(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $this->post('/admin/users', $this->data('personal_trainer') + ['specialization' => 'Pilates', 'bio' => '<script>private</script>'])->assertSessionHasNoErrors();
        $trainer = User::where('email', 'newaccount@example.com')->firstOrFail();
        $this->assertNotNull($trainer->trainerProfile);
        $this->get('/admin/users/'.$trainer->id)->assertOk()->assertSee('Pilates')->assertDontSee('<script>private</script>', false);
        $this->actingAs($trainer)->get('/')->assertRedirect('/classes');
        $this->get('/account')->assertOk()->assertSee('Pilates');
        $this->get('/admin/trainers')->assertForbidden();
    }

    public function test_archived_account_cannot_login_or_reuse_reserved_email(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $this->post('/admin/users', $this->data())->assertSessionHasNoErrors();
        $user = User::where('email', 'newaccount@example.com')->firstOrFail();
        $this->delete('/admin/users/'.$user->id, ['confirmed' => 1])->assertRedirect();
        $this->get('/admin/users/'.$user->id)->assertOk()->assertSee('Diarsipkan');
        $this->post('/admin/users', $this->data())->assertSessionHasErrors('email');
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'private-pass-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
