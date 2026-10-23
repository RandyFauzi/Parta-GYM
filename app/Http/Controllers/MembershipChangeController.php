<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembershipChangeRequest;
use App\Models\Member;
use App\Models\MembershipChange;
use App\Models\MembershipPeriod;
use App\Models\MembershipPlan;
use App\Services\MembershipChangeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MembershipChangeController extends Controller
{
    public function create(Request $request, Member $member, MembershipChangeService $service): View
    {
        abort_unless($request->user()->hasPermission('memberships.change'), 403);
        $data = $request->validate(['source_period_id' => 'nullable|uuid|exists:membership_periods,id', 'plan_id' => 'nullable|uuid|exists:membership_plans,id']);
        $periods = $member->periods()->with('invoice', 'entitlements')->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())->get();
        $source = isset($data['source_period_id']) ? $periods->firstWhere('id', $data['source_period_id']) : $periods->first();
        abort_if(isset($data['source_period_id']) && ! $source, 404);
        $plans = MembershipPlan::where('is_active', true)->orderBy('name')->get();
        $target = isset($data['plan_id']) ? $plans->firstWhere('id', $data['plan_id']) : $plans->first();

        return view('gym.membership-change', [
            'member' => $member, 'periods' => $periods, 'plans' => $plans, 'source' => $source, 'target' => $target,
            'preview' => $source && $target ? $service->preview($source, $target) : null,
            'changes' => MembershipChange::with('invoice')->where('member_id', $member->id)->latest()->paginate(10),
        ]);
    }

    public function store(MembershipChangeRequest $request, Member $member, MembershipChangeService $service): RedirectResponse
    {
        $data = $request->validated();
        $change = $service->replace($member, MembershipPeriod::findOrFail($data['source_period_id']), MembershipPlan::findOrFail($data['plan_id']), $request->user(), $data['quote'], $data['reason'], $data['request_key'], $data['method'], $data['reference'] ?? null);

        return redirect()->route('invoices.show', $change->invoice_id)->with('success', 'Paket diganti, selisih pembayaran dicatat, dan sisa kredit disimpan. Riwayat paket lama tetap utuh.');
    }
}
