<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuickRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registration(): array
    {
        return ['full_name' => 'New Member', 'phone' => '081234567890', 'plan_id' => MembershipPlan::factory()->create()->id, 'request_key' => (string) Str::uuid()];
    }

    public function test_qris_registration_creates_member_account_payment_and_active_membership(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $data = $this->registration() + ['payment_status' => 'paid', 'method' => 'qris', 'confirmed' => 1, 'create_account' => 1, 'email' => 'newmember@example.com', 'password' => 'private-password', 'role' => 'owner', 'total_idr' => 1];
        $this->post('/members', $data)->assertRedirect()->assertSessionHasNoErrors();
        $member = Member::firstOrFail();
        $invoice = Invoice::firstOrFail();
        $user = User::where('email', 'newmember@example.com')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('private-password', $user->password));
        $this->assertSame($user->id, $member->user_id);
        $this->assertSame('paid', $invoice->status);
        $this->assertDatabaseHas('payments', ['method' => 'qris', 'amount_idr' => 300000, 'staff_shift_id' => null]);
        $this->assertNotNull($member->currentPeriod);
        $this->actingAs($user)->get('/member')->assertOk();
        $this->get('/members')->assertForbidden();
    }

    public function test_pending_registration_creates_only_unpaid_invoice(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'receptionist']));
        $this->post('/members', $this->registration() + ['payment_status' => 'pending', 'method' => 'cash', 'confirmed' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['status' => 'unpaid']);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('membership_periods', 0);
    }

    public function test_cash_without_open_shift_rolls_back_entire_registration(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $this->post('/members', $this->registration() + ['payment_status' => 'paid', 'method' => 'cash', 'confirmed' => 1])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('members', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_paid_registration_requires_confirmation_and_active_plan(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $data = $this->registration() + ['payment_status' => 'paid', 'method' => 'qris'];
        $this->post('/members', $data)->assertSessionHasErrors('confirmed');
        MembershipPlan::whereKey($data['plan_id'])->update(['is_active' => false]);
        $this->post('/members', $data + ['confirmed' => 1])->assertSessionHasErrors('plan_id');
        $this->assertDatabaseCount('members', 0);
    }

    public function test_manual_methods_are_recorded_and_receipt_shows_correct_label(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        foreach (['qris' => 'QRIS', 'card' => 'Kartu / EDC', 'other' => 'Lainnya'] as $method => $label) {
            $member = Member::factory()->create();
            $plan = MembershipPlan::factory()->create();
            $invoice = app(MembershipService::class)->createInvoice($member, $plan, (string) Str::uuid());
            $data = ['method' => $method, 'reference' => 'Confirmed manually', 'confirmed' => 1];
            $this->post('/payments/'.$invoice->id, $data)->assertSessionHasNoErrors();
            $this->post('/payments/'.$invoice->id, $data)->assertSessionHasNoErrors();
            $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee($label);
        }
        $this->assertDatabaseCount('payments', 3);
        $this->assertDatabaseCount('membership_periods', 3);
    }
}
