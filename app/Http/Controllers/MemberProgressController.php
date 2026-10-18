<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberProgressRequest;
use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Models\TrainingSession;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberProgressController extends Controller
{
    public function __construct(private MembershipService $membershipService) {}

    public function index(Request $request): View
    {
        return view('gym.coaching-members', ['members' => Member::whereHas('trainingEnrollments', fn ($query) => $query->whereIn('status', ['confirmed', 'attended', 'no_show'])->whereHas('session.trainer', fn ($trainer) => $trainer->where('user_id', $request->user()->id)))->orderBy('full_name')->paginate(20)]);
    }

    public function member(Request $request, Member $member): View
    {
        abort_unless($member->isCoachedBy($request->user()), 403);

        return view('gym.coaching-member', ['member' => $member->load('progressRecords.trainer', 'progressRecords.session')]);
    }

    public function store(MemberProgressRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['training_session_id']) && $request->user()->role === 'personal_trainer') {
            abort_unless(TrainingSession::whereKey($data['training_session_id'])->whereHas('trainer', fn ($query) => $query->where('user_id', $request->user()->id))->exists(), 403);
        }
        $data['recorded_at'] = CarbonImmutable::parse($data['recorded_at'], config('gym.timezone'))->utc();
        $data['member_id'] = $member->id;
        $data['trainer_id'] = $request->user()->id;

        DB::transaction(function () use ($data, $member, $request) {
            $record = MemberProgressRecord::create($data);
            $this->membershipService->audit('member_progress.recorded', $record->id, [
                'member_id' => $member->id,
                'trainer_id' => $request->user()->id,
            ]);

        });

        return back()->with('success', 'Catatan evaluasi & progres latihan member berhasil disimpan.');
    }

    public function destroy(Request $request, MemberProgressRecord $record): RedirectResponse
    {
        abort_unless(
            $request->user()->role === 'owner' || ($request->user()->role === 'personal_trainer' && $record->trainer_id === $request->user()->id),
            403
        );

        DB::transaction(function () use ($record) {
            $memberId = $record->member_id;
            $record->delete();

            $this->membershipService->audit('member_progress.deleted', $record->id, [
                'member_id' => $memberId,
            ]);

        });

        return back()->with('success', 'Catatan progres berhasil dihapus.');
    }

    public function show(Request $request, Member $member, MemberProgressRecord $record): View
    {
        abort_unless(
            $request->user()->role === 'owner' || ($request->user()->role === 'personal_trainer' && $member->isCoachedBy($request->user())) ||
            ($request->user()->role === 'member' && $member->user_id === $request->user()->id),
            403
        );

        abort_unless($record->member_id === $member->id, 404);

        return view('gym.member-progress-detail', [
            'member' => $member,
            'record' => $record->load('trainer', 'session'),
        ]);
    }
}
