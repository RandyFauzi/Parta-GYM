<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperatingHoursRequest;
use App\Models\Checkin;
use App\Models\GymOperatingHour;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\MembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationalSettingsController extends Controller
{
    public function index(): View
    {
        return view('gym.operating-hours', ['hours' => GymOperatingHour::all()->keyBy('id')]);
    }

    public function update(OperatingHoursRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            foreach ($request->validated('days') as $weekday => $day) {
                GymOperatingHour::updateOrCreate(['id' => $weekday], [
                    'closed' => $day['closed'], 'opens_at' => $day['closed'] ? null : $day['opens_at'],
                    'closes_at' => $day['closed'] ? null : $day['closes_at'],
                ]);
            }
            app(MembershipService::class)->audit('gym.operating_hours_updated', 'weekly', $request->validated());
        }, 3);

        return back()->with('success', 'Jam operasional disimpan. Berlaku untuk check-in baru; batas checkout kunjungan yang sudah tercatat tetap dipertahankan.');
    }

    public function checkout(Request $request, Checkin $checkin, CheckoutService $service): RedirectResponse
    {
        $service->checkout($checkin, $request->user());

        return back()->with('success', 'Checkout tercatat. Riwayat kunjungan tetap tersimpan.');
    }
}
