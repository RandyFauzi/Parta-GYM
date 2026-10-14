<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberProfileRequest;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\MemberBarcode;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberPortalController extends Controller
{
    private function member(Request $request): Member
    {
        return Member::with('currentPeriod.invoice')->where('user_id', $request->user()->id)->firstOrFail();
    }

    public function home(Request $request): View
    {
        $member = $this->member($request);
        $today = CarbonImmutable::now(config('gym.timezone'))->startOfDay();
        $week = $member->checkins()->where('checked_in_at', '>=', $today->subDays(6)->utc())->where('checked_in_at', '<', $today->addDay()->utc())->get(['checked_in_at']);
        $byDay = $week->groupBy(fn ($visit) => $visit->checked_in_at->timezone(config('gym.timezone'))->format('Y-m-d'));
        $activity = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->subDays($i);
            $activity[] = ['label' => $day->locale('id')->isoFormat('ddd'), 'date' => $day->format('d M'), 'count' => $byDay->get($day->format('Y-m-d'), collect())->count()];
        }

        return view('member.home', compact('member', 'activity') + ['weekVisits' => $week->count(), 'totalVisits' => $member->checkins()->count(), 'latestVisit' => $member->checkins()->latest('checked_in_at')->first(), 'outstanding' => $member->invoices()->where('status', 'unpaid')->sum('total_idr'), 'periodVisits' => $member->currentPeriod ? $member->checkins()->where('membership_period_id', $member->currentPeriod->id)->count() : 0]);
    }

    public function membership(Request $request): View
    {
        $member = $this->member($request);

        return view('member.membership', ['member' => $member, 'plans' => MembershipPlan::where('is_active', true)->orderBy('price_idr')->get(), 'periods' => $member->periods()->with('invoice')->latest('starts_at')->paginate(8), 'periodVisits' => $member->currentPeriod ? $member->checkins()->where('membership_period_id', $member->currentPeriod->id)->count() : 0]);
    }

    public function visits(Request $request): View
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);
        $member = $this->member($request);
        $month = $request->filled('month') ? CarbonImmutable::createFromFormat('!Y-m', $request->month, config('gym.timezone')) : CarbonImmutable::now(config('gym.timezone'))->startOfMonth();
        $end = $month->addMonth();
        $query = $member->checkins()->where('checked_in_at', '>=', $month->utc())->where('checked_in_at', '<', $end->utc());
        $dates = (clone $query)->get(['checked_in_at'])->groupBy(fn ($visit) => $visit->checked_in_at->timezone(config('gym.timezone'))->format('Y-m-d'));
        $calendar = [];
        $start = $month->startOfWeek();
        for ($day = $start; $day->lessThan($end->endOfWeek()); $day = $day->addDay()) {
            $calendar[] = ['date' => $day, 'inMonth' => $day->month === $month->month, 'visits' => $dates->get($day->format('Y-m-d'), collect())->count()];
        }

        return view('member.visits', compact('member', 'month', 'calendar') + ['count' => $dates->sum(fn ($group) => $group->count()), 'uniqueDays' => $dates->count(), 'visits' => $query->latest('checked_in_at')->paginate(12)->withQueryString()]);
    }

    public function payments(Request $request): View
    {
        $request->validate(['status' => 'nullable|in:paid,unpaid']);
        $member = $this->member($request);

        return view('member.payments', ['member' => $member, 'outstanding' => $member->invoices()->where('status', 'unpaid')->sum('total_idr'), 'paid' => $member->invoices()->where('status', 'paid')->sum('total_idr'), 'invoices' => $member->invoices()->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))->latest()->paginate(10)->withQueryString()]);
    }

    public function card(Request $request, MemberBarcode $barcode): View
    {
        $member = $this->member($request);
        $code = str_pad((string) $request->user()->id, 12, '0', STR_PAD_LEFT);
        $usedVisits = $member->currentPeriod ? $member->checkins()->where('membership_period_id', $member->currentPeriod->id)->count() : 0;
        $eligible = $member->status === 'active' && $member->currentPeriod && ($member->currentPeriod->visit_limit === null || $usedVisits < $member->currentPeriod->visit_limit);

        return view('member.card', compact('member', 'code', 'usedVisits', 'eligible') + ['barcode' => $barcode->encode($code)]);
    }

    public function account(Request $request): View
    {
        return view('member.profile', ['member' => $this->member($request)]);
    }

    public function update(MemberProfileRequest $request, MembershipService $service): RedirectResponse
    {
        $member = $this->member($request);
        DB::transaction(function () use ($member, $request, $service) {
            $member->update($request->safe()->only(['full_name']));
            $service->audit('member.self_updated', $member->id);
        });

        return redirect()->route('portal.account')->with('success', 'Nama profil berhasil diperbarui.');
    }

    public function renewal(Request $request, MembershipService $service): RedirectResponse
    {
        $data = $request->validate(['plan_id' => 'required|uuid|exists:membership_plans,id', 'request_key' => 'required|uuid']);
        $invoice = $service->createInvoice($this->member($request), MembershipPlan::findOrFail($data['plan_id']), $data['request_key']);

        return redirect()->route('portal.invoice', $invoice)->with('success', 'Invoice paket dibuat. Selesaikan pembayaran di front desk untuk mengaktifkannya.');
    }

    public function receipt(Request $request, Invoice $invoice): View
    {
        $member = $this->member($request);
        abort_unless($invoice->member_id === $member->id, 404);

        return view('member.receipt', compact('member', 'invoice') + ['payment' => Payment::where('invoice_id', $invoice->id)->first()]);
    }

    public function password(Request $request, MembershipService $service): RedirectResponse
    {
        $data = $request->validate(['current_password' => 'required|string|current_password', 'password' => 'required|string|min:12|max:128|confirmed']);
        DB::transaction(function () use ($request, $data, $service) {
            $user = $request->user();
            $user->password = $data['password'];
            $user->remember_token = Str::random(60);
            $user->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $service->audit('member.password_changed', (string) $user->id);
        });
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kata sandi diperbarui. Masuk kembali dengan kata sandi baru.');
    }
}
