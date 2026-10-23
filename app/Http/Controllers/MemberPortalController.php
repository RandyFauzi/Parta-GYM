<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberProfileRequest;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\MemberDashboardService;
use App\Services\MemberProgressService;
use App\Services\MemberQrCode;
use App\Services\MembershipService;
use App\Services\WhatsAppService;
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

    public function home(Request $request, MemberDashboardService $dashboard): View
    {
        $member = $this->member($request);
        $request->validate(['activity' => 'nullable|in:gym,pilates']);
        $allBenefits = $dashboard->activeBenefits($member);
        $dashboardActivity = $dashboard->selectedActivity($member, $request->input('activity', $request->session()->get('portal.activity.'.$member->id)), $allBenefits);
        $benefits = $dashboard->forActivity($allBenefits, $dashboardActivity);
        $activityPeriod = $benefits->first()['period'] ?? null;
        $today = CarbonImmutable::now(config('gym.timezone'))->startOfDay();
        $week = $member->checkins()->where('checked_in_at', '>=', $today->subDays(6)->utc())->where('checked_in_at', '<', $today->addDay()->utc())->get(['checked_in_at']);
        $byDay = $week->groupBy(fn ($visit) => $visit->checked_in_at->timezone(config('gym.timezone'))->format('Y-m-d'));
        $activity = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->subDays($i);
            $activity[] = ['label' => $day->locale('id')->isoFormat('ddd'), 'date' => $day->format('d M'), 'count' => $byDay->get($day->format('Y-m-d'), collect())->count()];
        }

        $attended = $member->trainingEnrollments()->where('status', 'attended')->whereHas('session', fn ($session) => $session->where('activity', $dashboardActivity));
        $sessionWeek = (clone $attended)->where('attended_at', '>=', $today->subDays(6)->utc())->where('attended_at', '<', $today->addDay()->utc())->get(['attended_at']);
        if ($dashboardActivity === 'pilates') {
            $dates = $sessionWeek->groupBy(fn ($enrollment) => $enrollment->attended_at->timezone(config('gym.timezone'))->format('Y-m-d'));
            foreach ($activity as $index => &$day) {
                $day['count'] = $dates->get($today->subDays(6 - $index)->format('Y-m-d'), collect())->count();
            }
            unset($day);
        }

        return view('member.home', compact('member', 'activity', 'dashboardActivity', 'benefits', 'activityPeriod') + [
            'allBenefits' => $allBenefits,
            'bookingUrl' => app(WhatsAppService::class)->getBookingUrl($member, $dashboardActivity),
            'assignments' => $member->trainerAssignments()->active()->where('activity', $dashboardActivity)->whereHas('trainer.user', fn ($user) => $user->where('role', 'personal_trainer')->where('is_active', true))->with('trainer.user')->get(),
            'upcoming' => $member->trainingEnrollments()->where('class_enrollments.status', 'confirmed')->whereHas('session', fn ($session) => $session->where('activity', $dashboardActivity)->where('status', 'scheduled')->where('ends_at', '>', now()))->with('session.trainer.user', 'session.room')->join('training_sessions', 'training_sessions.id', '=', 'class_enrollments.training_session_id')->select('class_enrollments.*')->orderBy('training_sessions.starts_at')->limit(5)->get(),
            'weekVisits' => $dashboardActivity === 'gym' ? $week->count() : $sessionWeek->count(),
            'totalVisits' => $dashboardActivity === 'gym' ? $member->checkins()->count() : (clone $attended)->count(),
            'latestActivityAt' => $dashboardActivity === 'gym' ? $member->checkins()->latest('checked_in_at')->first()?->checked_in_at : (clone $attended)->whereNotNull('attended_at')->latest('attended_at')->first()?->attended_at,
            'outstanding' => $member->invoices()->where('status', 'unpaid')->sum('total_idr'),
        ]);
    }

    public function membership(Request $request, MemberDashboardService $dashboard): View
    {
        $member = $this->member($request);
        $request->validate(['activity' => 'nullable|in:gym,pilates']);
        $allBenefits = $dashboard->activeBenefits($member);
        $dashboardActivity = $dashboard->selectedActivity($member, $request->input('activity', $request->session()->get('portal.activity.'.$member->id)), $allBenefits);
        $benefits = $dashboard->forActivity($allBenefits, $dashboardActivity);

        return view('member.membership', compact('member', 'dashboardActivity', 'benefits') + ['activityPeriod' => $benefits->first()['period'] ?? null, 'plans' => MembershipPlan::where('is_active', true)->orderBy('price_idr')->get()->filter(fn (MembershipPlan $plan) => collect($plan->benefit_definitions)->keys()->contains(fn (string $key) => $dashboard->matches($key, $dashboardActivity))), 'periods' => $member->periods()->with('invoice')->latest('starts_at')->paginate(8)]);
    }

    public function switchActivity(Request $request): RedirectResponse
    {
        $data = $request->validate(['activity' => 'required|in:gym,pilates']);
        $member = $this->member($request);
        $request->session()->put('portal.activity.'.$member->id, $data['activity']);

        return redirect()->route('portal', ['activity' => $data['activity']]);
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

    public function card(Request $request, MemberQrCode $qrCode): View
    {
        $member = $this->member($request);
        $code = str_pad((string) $request->user()->id, 12, '0', STR_PAD_LEFT);
        $usedVisits = $member->currentPeriod ? $member->checkins()->where('membership_period_id', $member->currentPeriod->id)->count() : 0;
        $eligible = $member->status === 'active' && $member->currentPeriod && ($member->currentPeriod->gym_quota === null || $usedVisits < $member->currentPeriod->gym_quota);

        return view('member.card', compact('member', 'code', 'usedVisits', 'eligible') + ['qrCode' => $qrCode->encode($code)]);
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

    public function progress(Request $request, MemberProgressService $progressService): View
    {
        $member = $this->member($request);
        $request->validate(['activity' => 'nullable|in:gym,pilates']);
        $query = $member->progressRecords()->when($request->filled('activity'), fn ($query) => $query->where('activity', $request->input('activity')));
        $records = (clone $query)->with('trainer', 'session')->orderByDesc('created_at')->orderByDesc('id')->paginate(10)->withQueryString();

        return view('member.progress', [
            'member' => $member,
            'records' => $records,
            'latest' => (clone $query)->orderByDesc('created_at')->orderByDesc('id')->first(),
            'measurements' => $progressService->measurements($query),
        ]);
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
