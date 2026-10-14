<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Http\Requests\PlanRequest;
use App\Models\Checkin;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\StaffShift;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GymController extends Controller
{
    public function __construct(private MembershipService $service) {}

    public function home(Request $r): RedirectResponse
    {
        return redirect()->route(match ($r->user()->role) {
            'owner' => 'dashboard','receptionist' => 'checkin',default => 'portal'
        });
    }

    private function bounds(Request $r): array
    {
        $r->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $zone = config('gym.timezone');
        $now = CarbonImmutable::now($zone);
        $from = $r->filled('from') ? CarbonImmutable::parse($r->from, $zone)->startOfDay() : $now->startOfMonth();
        $to = $r->filled('to') ? CarbonImmutable::parse($r->to, $zone)->addDay()->startOfDay() : $now->addDay()->startOfDay();
        if ($from->greaterThanOrEqualTo($to) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal harus sebelum tanggal akhir; rentang maksimal 366 hari.']);
        }

        return [$from, $to];
    }

    public function dashboard(Request $r): View
    {
        [$from,$to] = $this->bounds($r);
        $today = CarbonImmutable::now(config('gym.timezone'))->startOfDay()->utc();
        $collections = Payment::where('paid_at', '>=', $from->utc())->where('paid_at', '<', $to->utc());
        $stats = ['active' => Member::where('status', 'active')->whereHas('currentPeriod')->count(), 'new' => Member::where('created_at', '>=', $from->utc())->where('created_at', '<', $to->utc())->count(), 'revenue' => (clone $collections)->sum('amount_idr'), 'visits' => Checkin::where('checked_in_at', '>=', $today)->where('checked_in_at', '<', $today->addDay())->count(), 'expiring' => Member::whereHas('currentPeriod', fn ($q) => $q->where('ends_at', '<=', now()->addDays(7)))->count(), 'outstanding' => Invoice::where('status', 'unpaid')->sum('total_idr')];
        $trend = [];
        foreach ((clone $collections)->get(['paid_at', 'amount_idr']) as $payment) {
            $day = $payment->paid_at->setTimezone(config('gym.timezone'))->format('Y-m-d');
            $trend[$day] = ($trend[$day] ?? 0) + $payment->amount_idr;
        }
        $days = [];
        for ($day = $from; $day->lessThan($to); $day = $day->addDay()) {
            $days[] = ['date' => $day->format('d M'), 'amount' => $trend[$day->format('Y-m-d')] ?? 0];
        }

        return view('gym.dashboard', compact('stats', 'days', 'from', 'to') + ['recent' => Payment::with('invoice.member')->latest('paid_at')->limit(5)->get(), 'expiring' => Member::with('currentPeriod.invoice')->whereHas('currentPeriod', fn ($q) => $q->where('ends_at', '<=', now()->addDays(7)))->limit(5)->get()]);
    }

    private function search(Request $r): Builder
    {
        $r->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:active,expired,suspended']);

        return Member::with('currentPeriod.invoice')->when($r->filled('q'), function ($q) use ($r) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $r->q).'%';
            $q->where(fn ($q) => $q->where('full_name', 'like', $term)->orWhere('member_number', 'like', $term)->orWhere('phone', 'like', $term));
        })->when($r->status === 'active', fn ($q) => $q->where('status', 'active')->whereHas('currentPeriod'))->when($r->status === 'expired', fn ($q) => $q->whereDoesntHave('currentPeriod'))->when($r->status === 'suspended', fn ($q) => $q->where('status', 'suspended'));
    }

    public function members(Request $r): View
    {
        return view('gym.members', ['members' => $this->search($r)->latest()->paginate(15)->withQueryString()]);
    }

    public function newMember(): View
    {
        return view('gym.member-form', ['plans' => MembershipPlan::where('is_active', true)->get()]);
    }

    public function storeMember(MemberRequest $r): RedirectResponse
    {
        $member = DB::transaction(function () use ($r) {
            $data = $r->validated();
            $member = Member::create(['full_name' => $data['full_name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null, 'member_number' => 'PG-'.strtoupper(Str::random(8))]);
            if ($r->filled('plan_id')) {
                $invoice = $this->service->createInvoice($member, MembershipPlan::findOrFail($data['plan_id']), $data['request_key']);
                if (($data['payment_status'] ?? 'pending') === 'paid') {
                    $this->service->settle($invoice, $r->user(), $data['method'], $data['reference'] ?? null);
                }
            }
            if ($r->boolean('create_account')) {
                $user = User::create(['name' => $member->full_name, 'email' => $data['email'], 'password' => $data['password']]);
                $member->update(['user_id' => $user->id]);
                $this->service->audit('member.account_linked', $member->id);
            }
            $this->service->audit('member.created', $member->id);

            return $member;
        });

        return redirect()->route('members.show', $member)->with('success', $r->input('payment_status') === 'paid' ? 'Anggota terdaftar, pembayaran tercatat, dan membership aktif.' : 'Anggota terdaftar. Konfirmasi pembayaran untuk mengaktifkan paket.');
    }

    public function member(Member $member): View
    {
        return view('gym.member', ['member' => $member->load('currentPeriod.invoice', 'periods.invoice'), 'plans' => MembershipPlan::where('is_active', true)->get(), 'invoices' => $member->invoices()->latest()->get(), 'visits' => $member->checkins()->latest('checked_in_at')->limit(20)->get()]);
    }

    public function updateMember(MemberRequest $r, Member $member): RedirectResponse
    {
        $member->update($r->safe()->only(['full_name', 'phone', 'email']));
        $this->service->audit('member.updated', $member->id);

        return back()->with('success', 'Profil diperbarui.');
    }

    public function invoice(Request $r, Member $member): RedirectResponse
    {
        $data = $r->validate(['plan_id' => 'required|uuid|exists:membership_plans,id', 'request_key' => 'required|uuid']);
        $this->service->createInvoice($member, MembershipPlan::findOrFail($data['plan_id']), $data['request_key']);

        return back()->with('success', 'Invoice dibuat. Keanggotaan aktif setelah pembayaran dikonfirmasi.');
    }

    public function payments(): View
    {
        return view('gym.payments', ['invoices' => Invoice::with('member', 'payment')->latest()->paginate(15)]);
    }

    public function pay(Request $r, Invoice $invoice): RedirectResponse
    {
        $data = $r->validate(['method' => 'required|in:cash,bank_transfer,qris,card,other', 'reference' => 'nullable|required_if:method,bank_transfer,other|string|max:120', 'confirmed' => 'required|accepted']);
        $this->service->settle($invoice, $r->user(), $data['method'], $data['reference'] ?? null);

        return back()->with('success', 'Pembayaran dikonfirmasi dan masa keanggotaan diaktifkan.');
    }

    public function checkinPage(Request $r): View
    {
        return view('gym.checkin', ['members' => $r->filled('q') ? $this->search($r)->limit(12)->get() : collect(), 'recent' => Checkin::with('member')->latest('checked_in_at')->limit(10)->get()]);
    }

    public function checkin(Request $r, Member $member): RedirectResponse
    {
        $r->validate(['verified' => 'required|accepted']);
        $reason = $this->service->checkin($member, $r->user());
        $messages = ['MEMBERSHIP_SUSPENDED' => 'Akses anggota ditangguhkan.', 'MEMBERSHIP_EXPIRED' => 'Tidak ada masa keanggotaan aktif. Silakan perpanjang.', 'DUPLICATE_CHECKIN' => 'Anggota sudah check-in dalam '.config('gym.duplicate_minutes').' menit terakhir.', 'VISIT_LIMIT_REACHED' => 'Kuota kunjungan sudah habis.'];

        return back()->with($reason ? 'error' : 'success', $reason ? $messages[$reason] : 'Check-in berhasil. Selamat berlatih, '.$member->full_name.'!');
    }

    public function plans(): View
    {
        return view('gym.plans', ['plans' => MembershipPlan::latest()->get()]);
    }

    public function storePlan(PlanRequest $r): RedirectResponse
    {
        $plan = MembershipPlan::create($r->validated());
        $this->service->audit('plan.created', $plan->id);

        return back()->with('success', 'Paket dibuat.');
    }

    public function updatePlan(PlanRequest $r, MembershipPlan $plan): RedirectResponse
    {
        $plan->update($r->validated());
        $this->service->audit('plan.updated', $plan->id);

        return back()->with('success', 'Paket diperbarui. Harga invoice sebelumnya tetap tersimpan.');
    }

    public function shifts(Request $r): View
    {
        $current = StaffShift::where('active_staff_id', $r->user()->id)->first();
        $cash = $current ? Payment::where('staff_shift_id', $current->id)->sum('amount_idr') : 0;

        return view('gym.shifts', ['current' => $current, 'cash' => $cash, 'shifts' => StaffShift::when($r->user()->role !== 'owner', fn ($q) => $q->where('staff_id', $r->user()->id))->latest()->paginate(15)]);
    }

    public function openShift(Request $r): RedirectResponse
    {
        $data = $r->validate(['opening_cash_idr' => 'required|integer|min:0|max:1000000000']);
        DB::transaction(function () use ($r, $data) {
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            if (StaffShift::where('active_staff_id', $r->user()->id)->exists()) {
                throw ValidationException::withMessages(['shift' => 'Shift Anda masih terbuka.']);
            } $shift = StaffShift::create($data + ['staff_id' => $r->user()->id, 'active_staff_id' => $r->user()->id]);
            $this->service->audit('shift.opened', $shift->id);
        });

        return back()->with('success', 'Shift dibuka.');
    }

    public function closeShift(Request $r, StaffShift $shift): RedirectResponse
    {
        abort_unless($shift->staff_id === $r->user()->id, 403);
        $data = $r->validate(['actual_cash_idr' => 'required|integer|min:0|max:1000000000']);
        DB::transaction(function () use ($shift, $data) {
            $shift = StaffShift::whereKey($shift->id)->lockForUpdate()->firstOrFail();
            if ($shift->closed_at) {
                throw ValidationException::withMessages(['shift' => 'Shift sudah ditutup.']);
            } $expected = $shift->opening_cash_idr + Payment::where('staff_shift_id', $shift->id)->sum('amount_idr');
            $shift->update($data + ['expected_cash_idr' => $expected, 'cash_difference_idr' => $data['actual_cash_idr'] - $expected, 'closed_at' => now(), 'active_staff_id' => null]);
            $this->service->audit('shift.closed', $shift->id, ['difference' => $data['actual_cash_idr'] - $expected]);
        });

        return back()->with('success', 'Shift ditutup dan rekonsiliasi disimpan.');
    }

    public function portal(Request $r): View
    {
        $member = Member::with('currentPeriod.invoice', 'periods.invoice')->where('user_id', $r->user()->id)->firstOrFail();

        return view('gym.portal', ['member' => $member, 'invoices' => $member->invoices()->latest()->get(), 'visits' => $member->checkins()->latest('checked_in_at')->limit(30)->get()]);
    }

    public function profile(Request $r): RedirectResponse
    {
        $data = $r->validate(['full_name' => 'required|string|max:120']);
        $member = Member::where('user_id', $r->user()->id)->firstOrFail();
        $member->update($data);
        $this->service->audit('member.self_updated', $member->id);

        return back()->with('success', 'Nama profil diperbarui.');
    }

    public function receipt(Request $r, Invoice $invoice): View
    {
        abort_unless(in_array($r->user()->role, ['owner', 'receptionist'], true) || $invoice->member->user_id === $r->user()->id, 403);

        return view('gym.receipt', ['invoice' => $invoice->load('member'), 'payment' => Payment::where('invoice_id', $invoice->id)->first()]);
    }

    public function reports(Request $r): View
    {
        [$from,$to] = $this->bounds($r);

        return view('gym.reports', ['from' => $from, 'to' => $to, 'payments' => Payment::with('invoice.member')->where('paid_at', '>=', $from->utc())->where('paid_at', '<', $to->utc())->latest('paid_at')->paginate(30)->withQueryString(), 'totals' => Payment::where('paid_at', '>=', $from->utc())->where('paid_at', '<', $to->utc())->selectRaw('method, SUM(amount_idr) AS total')->groupBy('method')->get()]);
    }

    public function export(Request $r): StreamedResponse
    {
        [$from,$to] = $this->bounds($r);

        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Tanggal UTC', 'Metode', 'Rupiah'], ',', '"', '');
            foreach (Payment::with('invoice')->where('paid_at', '>=', $from->utc())->where('paid_at', '<', $to->utc())->cursor() as $p) {
                fputcsv($out, [$p->invoice->invoice_number, $p->paid_at->toIso8601String(), $p->method, $p->amount_idr], ',', '"', '');
            } fclose($out);
        }, 'gym-collections.csv', ['Content-Type' => 'text/csv']);
    }

    public function audit(): View
    {
        return view('gym.audit', ['logs' => DB::table('audit_logs')->leftJoin('users', 'users.id', '=', 'audit_logs.actor_user_id')->select('audit_logs.*', 'users.name')->orderByDesc('audit_logs.id')->paginate(30)]);
    }
}
