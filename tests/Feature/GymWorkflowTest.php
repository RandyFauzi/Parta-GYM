<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\StaffShift;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GymWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role = 'owner'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function purchase(?Member $member = null, ?MembershipPlan $plan = null): Invoice
    {
        return app(MembershipService::class)->createInvoice($member ?? Member::factory()->create(), $plan ?? MembershipPlan::factory()->create(), (string) Str::uuid());
    }

    public function test_guests_must_login_and_roles_are_server_enforced(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->actingAs($this->staff('receptionist'))->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($this->staff('member'))->get('/members')->assertForbidden();
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'password' => 'test-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => 'test-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_and_duplicate_phone_protection(): void
    {
        $this->actingAs($this->staff('receptionist'));
        $data = ['full_name' => 'Nadia Test', 'phone' => '081234567890', 'request_key' => (string) Str::uuid()];
        $this->post('/members', $data)->assertRedirect();
        $this->assertDatabaseHas('members', ['phone' => '+6281234567890']);
        $this->post('/members', $data)->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('members', 1);
    }

    public function test_invoice_is_idempotent_and_preserves_price_snapshot(): void
    {
        $member = Member::factory()->create();
        $plan = MembershipPlan::factory()->create();
        $key = (string) Str::uuid();
        $service = app(MembershipService::class);
        $first = $service->createInvoice($member, $plan, $key);
        $plan->update(['price_idr' => 999999]);
        $second = $service->createInvoice($member, $plan, $key);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(300000, $second->total_idr);
        $this->assertDatabaseCount('membership_periods', 0);
    }

    public function test_confirmed_payment_activates_once(): void
    {
        $invoice = $this->purchase();
        $staff = $this->staff();
        $service = app(MembershipService::class);
        $service->settle($invoice, $staff, 'bank_transfer', 'verified');
        $service->settle($invoice, $staff, 'bank_transfer', 'verified');
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('membership_periods', 1);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_renewal_keeps_remaining_days_and_expired_renewal_starts_now(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-31 10:00', 'Asia/Makassar'));
        $staff = $this->staff();
        $invoice = $this->purchase();
        $service = app(MembershipService::class);
        $service->settle($invoice, $staff, 'bank_transfer', 'test');
        $first = MembershipPeriod::firstOrFail();
        $this->assertSame('2026-02-28', $first->ends_at->timezone('Asia/Makassar')->format('Y-m-d'));
        $renew = $service->createInvoice($invoice->member, MembershipPlan::firstOrFail(), (string) Str::uuid());
        $service->settle($renew, $staff, 'bank_transfer', 'test2');
        $second = MembershipPeriod::where('source_invoice_id', $renew->id)->firstOrFail();
        $this->assertTrue($second->starts_at->equalTo($first->ends_at));
        $this->travelTo(CarbonImmutable::parse('2027-05-15'));
        $renew = $service->createInvoice($invoice->member, MembershipPlan::firstOrFail(), (string) Str::uuid());
        $service->settle($renew, $staff, 'bank_transfer', 'test3');
        $this->assertTrue(MembershipPeriod::where('source_invoice_id', $renew->id)->firstOrFail()->starts_at->equalTo(now()));
    }

    public function test_calendar_leap_year_and_half_open_expiry(): void
    {
        $service = app(MembershipService::class);
        $start = CarbonImmutable::parse('2024-02-29', 'Asia/Makassar');
        $this->assertSame('2025-02-28', $service->periodEnd($start, 'year', 1)->format('Y-m-d'));
        $invoice = $this->purchase();
        $staff = $this->staff();
        $service->settle($invoice, $staff, 'bank_transfer', 'test');
        $period = MembershipPeriod::firstOrFail();
        $this->travelTo($period->ends_at);
        $this->assertSame('MEMBERSHIP_EXPIRED', $service->checkin($invoice->member, $staff));
    }

    public function test_checkin_success_duplicate_and_visit_limit(): void
    {
        $plan = MembershipPlan::factory()->create(['visit_limit' => 1]);
        $invoice = $this->purchase(plan: $plan);
        $staff = $this->staff();
        $service = app(MembershipService::class);
        $service->settle($invoice, $staff, 'bank_transfer', 'test');
        $this->assertNull($service->checkin($invoice->member, $staff));
        $this->assertSame('DUPLICATE_CHECKIN', $service->checkin($invoice->member, $staff));
        $this->travel(16)->minutes();
        $this->assertSame('VISIT_LIMIT_REACHED', $service->checkin($invoice->member, $staff));
        $this->assertDatabaseCount('checkins', 1);
        $this->assertDatabaseCount('checkin_attempts', 3);
    }

    public function test_unpaid_and_suspended_members_are_denied(): void
    {
        $invoice = $this->purchase();
        $staff = $this->staff();
        $service = app(MembershipService::class);
        $this->assertSame('MEMBERSHIP_EXPIRED', $service->checkin($invoice->member, $staff));
        $service->settle($invoice, $staff, 'bank_transfer', 'test');
        $invoice->member->update(['status' => 'suspended']);
        $this->assertSame('MEMBERSHIP_SUSPENDED', $service->checkin($invoice->member, $staff));
        $this->assertDatabaseCount('checkins', 0);
    }

    public function test_cash_requires_shift_and_reconciliation_is_immutable(): void
    {
        $staff = $this->staff('receptionist');
        $this->actingAs($staff);
        $invoice = $this->purchase();
        $payload = ['method' => 'cash', 'confirmed' => 1];
        $this->post('/payments/'.$invoice->id, $payload)->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 0);
        $this->post('/shifts', ['opening_cash_idr' => 100000])->assertRedirect();
        $this->post('/payments/'.$invoice->id, $payload)->assertRedirect();
        $shift = StaffShift::firstOrFail();
        $this->post('/shifts/'.$shift->id.'/close', ['actual_cash_idr' => 399000])->assertRedirect();
        $this->assertSame(-1000, $shift->fresh()->cash_difference_idr);
        $this->post('/shifts/'.$shift->id.'/close', ['actual_cash_idr' => 0])->assertSessionHasErrors('shift');
        $this->assertSame(399000, $shift->fresh()->actual_cash_idr);
    }

    public function test_member_cannot_access_other_invoice(): void
    {
        $memberUser = $this->staff('member');
        Member::factory()->create(['user_id' => $memberUser->id]);
        $invoice = $this->purchase();
        $this->actingAs($memberUser)->get('/invoices/'.$invoice->id)->assertForbidden();
        $this->get('/member')->assertOk()->assertDontSee($invoice->invoice_number);
    }

    public function test_transfer_requires_reference_and_confirmation(): void
    {
        $this->actingAs($this->staff());
        $invoice = $this->purchase();
        $this->post('/payments/'.$invoice->id, ['method' => 'bank_transfer'])->assertSessionHasErrors(['reference', 'confirmed']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_all_staff_screens_render_and_reporting_matches_payments(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        $invoice = $this->purchase();
        app(MembershipService::class)->settle($invoice, $staff, 'bank_transfer', 'test');
        foreach (['/admin/dashboard', '/members', '/members/new', '/members/'.$invoice->member_id, '/payments', '/frontdesk/checkin?q=TEST', '/shifts', '/admin/membership-plans', '/admin/reports', '/admin/audit', '/invoices/'.$invoice->id] as $url) {
            $this->get($url)->assertOk();
        } $this->get('/admin/dashboard')->assertSee('Rp 300.000');
        $this->get('/admin/reports/export')->assertOk()->assertDownload('gym-collections.csv');
    }

    public function test_negative_price_and_unauthorized_plan_changes_rejected(): void
    {
        $this->actingAs($this->staff())->post('/admin/membership-plans',['name' => 'Bad', 'duration_unit' => 'month', 'duration_value' => 1, 'price_idr' => -1, 'is_active' => 1])->assertSessionHasErrors('price_idr');
        $this->actingAs($this->staff('receptionist'))->post('/admin/membership-plans',[])->assertForbidden();
    }
}
