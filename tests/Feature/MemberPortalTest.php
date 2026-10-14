<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    private function account(): Member
    {
        $user = User::factory()->create(['role' => 'member', 'password' => 'original-password']);
        $member = Member::factory()->create(['user_id' => $user->id, 'full_name' => 'My Private Profile']);
        $this->actingAs($user);

        return $member;
    }

    public function test_member_pages_are_separate_and_do_not_expose_another_member(): void
    {
        $this->account();
        Member::factory()->create(['full_name' => 'OTHER MEMBER PRIVATE']);
        foreach (['/member', '/member/membership', '/member/visits', '/member/payments', '/member/profile'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('OTHER MEMBER PRIVATE')->assertSee('Navigasi anggota mobile');
        }
        $this->get('/member')->assertSee('7 hari terakhir');
        $this->get('/member/visits')->assertSee('Riwayat check-in');
        $this->get('/member/profile')->assertSee('Keamanan akun');
    }

    public function test_member_renewal_creates_only_own_unpaid_invoice_once(): void
    {
        $member = $this->account();
        $other = Member::factory()->create();
        $plan = MembershipPlan::factory()->create();
        $key = (string) Str::uuid();
        $data = ['plan_id' => $plan->id, 'request_key' => $key, 'member_id' => $other->id, 'total_idr' => 1, 'status' => 'paid'];
        $this->post('/member/renewals', $data)->assertRedirect();
        $this->post('/member/renewals', $data)->assertRedirect();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseHas('invoices', ['member_id' => $member->id, 'total_idr' => 300000, 'status' => 'unpaid']);
        $this->assertDatabaseCount('membership_periods', 0);
        $invoice = Invoice::firstOrFail();
        $this->get('/member/invoices/'.$invoice->id)->assertOk()->assertSee('Rp 300.000');
    }

    public function test_inactive_plan_cannot_be_purchased_and_other_invoice_returns_404(): void
    {
        $this->account();
        $plan = MembershipPlan::factory()->create(['is_active' => false]);
        $this->post('/member/renewals', ['plan_id' => $plan->id, 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('plan_id');
        $this->assertDatabaseCount('invoices', 0);
        $other = Member::factory()->create();
        $plan->update(['is_active' => true]);
        $invoice = app(MembershipService::class)->createInvoice($other, $plan, (string) Str::uuid());
        $this->get('/member/invoices/'.$invoice->id)->assertNotFound();
        $this->get('/member/payments')->assertDontSee($invoice->invoice_number);
    }

    public function test_profile_update_whitelists_name_and_preserves_login_identifiers(): void
    {
        $member = $this->account();
        $this->put('/member/profile', ['full_name' => 'Updated Member', 'phone' => '+628000000000', 'user_id' => null, 'status' => 'suspended'])->assertRedirect('/member/profile');
        $this->assertSame('Updated Member', $member->fresh()->full_name);
        $this->assertSame($member->phone, $member->fresh()->phone);
        $this->assertSame('active', $member->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.self_updated']);
        $this->put('/member/profile', ['full_name' => ''])->assertSessionHasErrors('full_name');
    }

    public function test_visits_calendar_uses_local_month_and_paginated_owned_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-07-15 10:00', 'Asia/Makassar'));
        $member = $this->account();
        $staff = User::factory()->create(['role' => 'owner']);
        $service = app(MembershipService::class);
        $invoice = $service->createInvoice($member, MembershipPlan::factory()->create(), (string) Str::uuid());
        $service->settle($invoice, $staff, 'bank_transfer', 'test');
        $this->travelTo(CarbonImmutable::parse('2026-07-31 21:00', 'Asia/Makassar'));
        $service->checkin($member, $staff);
        $this->travelTo(CarbonImmutable::parse('2026-08-01 00:30', 'Asia/Makassar'));
        $service->checkin($member, $staff);
        $this->get('/member/visits?month=2026-07')->assertOk()->assertViewHas('count', 1)->assertViewHas('uniqueDays', 1);
        $this->get('/member/visits?month=2026-08')->assertOk()->assertViewHas('count', 1);
        $this->get('/member/visits?month=invalid')->assertSessionHasErrors('month');
    }

    public function test_invoice_status_filter_preserves_payment_history(): void
    {
        $member = $this->account();
        $service = app(MembershipService::class);
        $plan = MembershipPlan::factory()->create();
        $paid = $service->createInvoice($member, $plan, (string) Str::uuid());
        $unpaid = $service->createInvoice($member, $plan, (string) Str::uuid());
        $service->settle($paid, User::factory()->create(['role' => 'owner']), 'bank_transfer', 'test');
        $this->get('/member/payments?status=paid')->assertSee($paid->invoice_number)->assertDontSee($unpaid->invoice_number);
        $this->get('/member/payments?status=unpaid')->assertSee($unpaid->invoice_number)->assertDontSee($paid->invoice_number);
    }

    public function test_password_change_requires_current_password_and_closes_sessions(): void
    {
        $this->account();
        $user = auth()->user();
        DB::table('sessions')->insert(['id' => 'other-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->put('/member/password', ['current_password' => 'wrong', 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'])->assertSessionHasErrors('current_password');
        $this->assertAuthenticated();
        $this->put('/member/password', ['current_password' => 'original-password', 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.password_changed']);
    }

    public function test_staff_cannot_enter_member_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'receptionist']));
        $this->get('/member/membership')->assertForbidden();
        $this->post('/member/renewals',[])->assertForbidden();
    }
}
