<?php

namespace Tests\Feature;

use App\Models\Checkin;
use App\Models\GymOperatingHour;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Database\Seeders\GymOperatingHourSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Member
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 08:00', config('gym.timezone')));
        $this->actingAs(User::factory()->create(['role' => 'owner']));
        $member = Member::factory()->create();
        $invoice = app(MembershipService::class)->createInvoice($member, MembershipPlan::factory()->create(), (string) Str::uuid());
        app(MembershipService::class)->settle($invoice, auth()->user(), 'qris', null);

        return $member;
    }

    public function test_auto_checkout_uses_recorded_closing_time_and_is_idempotent(): void
    {
        $member = $this->member();
        $this->seed(GymOperatingHourSeeder::class);
        $this->assertNull(app(MembershipService::class)->checkin($member, auth()->user()));
        $visit = Checkin::firstOrFail();
        $this->assertSame('22:00', $visit->checkout_due_at->timezone(config('gym.timezone'))->format('H:i'));
        $this->travel(16)->minutes();
        $this->assertSame('DUPLICATE_CHECKIN', app(MembershipService::class)->checkin($member, auth()->user()));
        $this->travelTo(CarbonImmutable::parse('2026-10-26 23:00', config('gym.timezone')));
        $this->artisan('gym:checkout-due')->expectsOutput('1 kunjungan ditutup otomatis.')->assertSuccessful();
        $this->artisan('gym:checkout-due')->expectsOutput('0 kunjungan ditutup otomatis.')->assertSuccessful();
        $this->assertSame('22:00', $visit->fresh()->checked_out_at->timezone(config('gym.timezone'))->format('H:i'));
        $this->assertSame('automatic', $visit->fresh()->checkout_method);
        $this->assertDatabaseCount('checkins', 1);
        $this->assertDatabaseCount('membership_periods', 1);
    }

    public function test_manual_checkout_is_staff_only_and_does_not_overwrite_original_exit(): void
    {
        $member = $this->member();
        app(MembershipService::class)->checkin($member, auth()->user());
        $visit = Checkin::firstOrFail();
        $this->travel(20)->minutes();
        $this->post(route('checkout.store', $visit))->assertSessionHas('success');
        $first = $visit->fresh()->checked_out_at;
        $this->travel(10)->minutes();
        $this->post(route('checkout.store', $visit))->assertSessionHas('success');
        $this->assertTrue($visit->fresh()->checked_out_at->equalTo($first));
        $this->assertSame('manual', $visit->fresh()->checkout_method);
        $this->actingAs(User::factory()->create(['role' => 'member']))->post(route('checkout.store', $visit))->assertForbidden();
    }

    public function test_overnight_hours_use_previous_days_closing_and_settings_do_not_rewrite_open_visit(): void
    {
        $member = $this->member();
        GymOperatingHour::factory()->create(['id' => 1, 'opens_at' => '20:00', 'closes_at' => '02:00']);
        $this->travelTo(CarbonImmutable::parse('2026-10-27 01:00', config('gym.timezone')));
        $this->assertNull(app(MembershipService::class)->checkin($member, auth()->user()));
        $visit = Checkin::firstOrFail();
        $this->assertSame('2026-10-27 02:00', $visit->checkout_due_at->timezone(config('gym.timezone'))->format('Y-m-d H:i'));
        GymOperatingHour::findOrFail(1)->update(['closes_at' => '01:30']);
        $this->travelTo(CarbonImmutable::parse('2026-10-27 01:45', config('gym.timezone')));
        $this->assertSame(0, app(CheckoutService::class)->closeDue());
        $this->travelTo(CarbonImmutable::parse('2026-10-27 02:10', config('gym.timezone')));
        $this->assertSame(1, app(CheckoutService::class)->closeDue());
        $this->assertSame('02:00', $visit->fresh()->checked_out_at->timezone(config('gym.timezone'))->format('H:i'));
    }

    public function test_legacy_visits_without_deadline_are_not_fabricated_and_hours_are_owner_only(): void
    {
        $member = $this->member();
        app(MembershipService::class)->checkin($member, auth()->user());
        $visit = Checkin::firstOrFail();
        $this->seed(GymOperatingHourSeeder::class);
        $this->travel(2)->days();
        $this->assertSame(0, app(CheckoutService::class)->closeDue());
        $this->assertNull($visit->fresh()->checked_out_at);
        $this->get(route('operating-hours'))->assertOk();
        $days = [];
        foreach (range(0, 6) as $day) {
            $days[$day] = ['closed' => false, 'opens_at' => '06:00', 'closes_at' => '22:00'];
        }
        $this->put(route('operating-hours.update'), ['days' => $days])->assertSessionHasNoErrors();
        $days[1]['closes_at'] = '06:00';
        $this->put(route('operating-hours.update'), ['days' => $days])->assertSessionHasErrors('days.1');
        $this->actingAs(User::factory()->create(['role' => 'receptionist']))->get(route('operating-hours'))->assertForbidden();
    }

    public function test_closed_gym_rejects_checkin_and_late_manual_checkout_uses_due_time(): void
    {
        $member = $this->member();
        $this->seed(GymOperatingHourSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-10-26 05:00', config('gym.timezone')));
        $this->post(route('checkin.store', $member), ['verified' => 1])->assertSessionHasErrors('checkin');
        $this->assertDatabaseCount('checkins', 0);
        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:00', config('gym.timezone')));
        $this->post(route('checkin.store', $member), ['verified' => 1])->assertSessionHas('success');
        $visit = Checkin::firstOrFail();
        $this->travelTo(CarbonImmutable::parse('2026-10-26 23:00', config('gym.timezone')));
        $this->post(route('checkout.store', $visit))->assertSessionHas('success');
        $this->assertSame('automatic', $visit->fresh()->checkout_method);
        $this->assertSame('22:00', $visit->fresh()->checked_out_at->timezone(config('gym.timezone'))->format('H:i'));
    }
}
