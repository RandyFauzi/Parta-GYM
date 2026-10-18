<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\WhatsAppService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerTypeAndWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function getStaffUser(): User
    {
        return User::factory()->create(['role' => 'receptionist']);
    }

    public function test_registration_with_customer_type_student(): void
    {
        $this->actingAs($this->getStaffUser());

        $response = $this->post('/members', [
            'full_name' => 'Siswa Pintar',
            'phone' => '081234567891',
            'customer_type' => 'student',
            'student_id_number' => 'NIS-998877',
            'notes' => 'Diskon pelajar SMA',
            'request_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $member = Member::where('phone', '+6281234567891')->firstOrFail();
        $this->assertSame('student', $member->customer_type);
        $this->assertSame('NIS-998877', $member->student_id_number);
        $this->assertSame('Diskon pelajar SMA', $member->notes);
        $this->assertSame('Pelajar / Mahasiswa', $member->customer_type_label);
        $this->assertTrue($member->isStudent());
        $this->assertFalse($member->isNonMember());
    }

    public function test_registration_with_customer_type_non_member(): void
    {
        $this->actingAs($this->getStaffUser());

        $response = $this->post('/members', [
            'full_name' => 'Tamu Harian',
            'phone' => '081234567892',
            'customer_type' => 'non_member',
            'notes' => 'Tiket visit 1 hari',
            'request_key' => (string) Str::uuid(),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $member = Member::where('phone', '+6281234567892')->firstOrFail();
        $this->assertSame('non_member', $member->customer_type);
        $this->assertSame('Non-Member / Harian', $member->customer_type_label);
        $this->assertTrue($member->isNonMember());
        $this->assertFalse($member->isStudent());
    }

    public function test_search_returning_members_on_registration_page(): void
    {
        $this->actingAs($this->getStaffUser());

        $existing = Member::factory()->create([
            'full_name' => 'Member Lama Reaktivasi',
            'phone' => '+6281299998888',
            'customer_type' => 'member',
        ]);

        $response = $this->get('/members/new?search_returning=Lama');
        $response->assertOk();
        $response->assertSee('Member Lama Reaktivasi');
        $response->assertSee('Pilih &amp; Tambah Paket', false);
    }

    public function test_filter_members_by_customer_type(): void
    {
        $this->actingAs($this->getStaffUser());

        Member::factory()->create(['full_name' => 'Reguler Guy', 'customer_type' => 'member']);
        Member::factory()->create(['full_name' => 'Student Girl', 'customer_type' => 'student']);
        Member::factory()->create(['full_name' => 'Daily Visitor', 'customer_type' => 'non_member']);

        $response = $this->get('/members?customer_type=student');
        $response->assertOk();
        $response->assertSee('Student Girl');
        $response->assertDontSee('Reguler Guy');
        $response->assertDontSee('Daily Visitor');
    }

    public function test_whatsapp_service_generates_correct_urls_and_messages(): void
    {
        $service = new WhatsAppService;

        $formatted = $service->formatPhone('+62812-3456-7890');
        $this->assertSame('6281234567890', $formatted);

        $member = Member::factory()->create([
            'full_name' => 'Andi Pratama',
            'phone' => '+6281234567890',
            'customer_type' => 'student',
        ]);

        $passUrl = $service->getMemberPassUrl($member);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $passUrl);
        $this->assertStringContainsString('KARTU%20ANGGOTA%20DIGITAL', $passUrl);

        $plan = MembershipPlan::factory()->create(['name' => 'Gym Monthly 30 Hari', 'price_idr' => 300000]);
        $membershipService = app(MembershipService::class);
        $invoice = $membershipService->createInvoice($member, $plan, (string) Str::uuid());
        $invoice->update(['status' => 'paid']);

        Payment::create([
            'invoice_id' => $invoice->id,
            'staff_id' => $this->getStaffUser()->id,
            'amount_idr' => 300000,
            'method' => 'qris',
            'paid_at' => now(),
        ]);

        $receiptUrl = $service->getReceiptUrl($invoice);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $receiptUrl);
        $this->assertStringContainsString('STRUK%20PEMBAYARAN', $receiptUrl);

        $period = MembershipPeriod::create([
            'member_id' => $member->id,
            'subscription_id' => $invoice->subscription_id,
            'source_invoice_id' => $invoice->id,
            'starts_at' => CarbonImmutable::now()->subDays(20),
            'ends_at' => CarbonImmutable::now()->addDays(5),
            'status' => 'active',
        ]);

        $reminderUrl = $service->getExpiryReminderUrl($member->refresh());
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $reminderUrl);
        $this->assertStringContainsString('PENGINGAT%20MASA%20KEANGGOTAAN', $reminderUrl);
    }
}
