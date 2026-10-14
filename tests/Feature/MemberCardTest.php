<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Services\MemberBarcode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_card_is_private_and_staff_can_resolve_barcode(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $member = Member::factory()->create(['user_id' => $user->id, 'full_name' => 'Private card name']);
        $code = str_pad((string) $user->id, 12, '0', STR_PAD_LEFT);
        $this->actingAs($user)->get('/member/check-in')->assertOk()->assertSee($code)->assertSee('Private card name')->assertSee('Akses belum tersedia');
        $other = User::factory()->create(['role' => 'member']);
        Member::factory()->create(['user_id' => $other->id]);
        $this->actingAs($other)->get('/member/check-in')->assertOk()->assertDontSee('Private card name');
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get('/frontdesk/checkin?q='.$code)->assertOk()->assertSee('Private card name');
        $this->get('/member/check-in')->assertForbidden();
    }

    public function test_barcode_has_start_stop_and_quiet_zones(): void
    {
        $result = app(MemberBarcode::class)->encode('000000000001');
        $this->assertCount(70, $result['bars']);
        $this->assertSame(10, $result['bars'][0]['x']);
        $last = $result['bars'][69];
        $this->assertGreaterThanOrEqual(10, $result['width'] - $last['x'] - $last['width']);
    }
}
