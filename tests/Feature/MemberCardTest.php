<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Services\MemberQrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_card_is_private_and_staff_can_resolve_qr_identifier(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id, 'full_name' => 'Private card name']);
        $code = str_pad((string) $user->id, 12, '0', STR_PAD_LEFT);
        $this->actingAs($user)->get('/member/check-in')->assertOk()->assertSee($code)->assertSee('QR Code kartu anggota')->assertSee('viewBox="0 0 29 29"', false)->assertSee('Private card name')->assertSee('Akses belum tersedia')->assertDontSee('member-pass-barcode');
        $other = User::factory()->create(['role' => 'member']);
        Member::factory()->create(['user_id' => $other->id]);
        $this->actingAs($other)->get('/member/check-in')->assertOk()->assertDontSee('Private card name');
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get('/frontdesk/checkin?q='.$code)->assertOk()->assertSee('Private card name');
        $this->get('/member/check-in')->assertForbidden();
    }

    public function test_qr_code_has_version_one_dimensions_and_finder_patterns(): void
    {
        $result = app(MemberQrCode::class)->encode('000000000002');
        $this->assertSame('9b422b2e9a1b1dc17a6c65157749271beccaca5a1533ac01e72f87c817d4353a', hash('sha256', json_encode($result)));
        $this->assertCount(21, $result);
        foreach ($result as $row) {
            $this->assertCount(21, $row);
        }
        foreach ([[0, 0], [14, 0], [0, 14]] as [$x, $y]) {
            $this->assertTrue($result[$y][$x]);
            $this->assertFalse($result[$y + 1][$x + 1]);
            $this->assertTrue($result[$y + 3][$x + 3]);
        }
    }

    public function test_qr_encoder_rejects_non_member_payloads(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(MemberQrCode::class)->encode('<script>');
    }
}
