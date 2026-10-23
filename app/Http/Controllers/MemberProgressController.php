<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberProgressRequest;
use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Services\MemberProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberProgressController extends Controller
{
    public function __construct(private MemberProgressService $progressService) {}

    public function index(Request $request): View
    {
        return view('gym.coaching-members', ['members' => Member::whereHas('trainerAssignments', fn ($query) => $query->active()->whereHas('trainer', fn ($trainer) => $trainer->where('user_id', $request->user()->id)))->orderBy('full_name')->paginate(20)]);
    }

    public function member(Request $request, Member $member): View
    {
        abort_unless($member->isCoachedBy($request->user()), 403);

        $query = $member->progressRecords()->visibleToTrainer($request->user(), $member);

        return view('gym.coaching-member', [
            'member' => $member,
            'records' => (clone $query)->with('trainer', 'session')->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
            'measurements' => $this->progressService->measurements($query),
            'sessions' => $member->trainingEnrollments()->whereIn('status', ['confirmed', 'attended', 'no_show'])->whereHas('session', fn ($session) => $session->where('status', '!=', 'cancelled')->where('starts_at', '<=', now())->whereHas('trainer', fn ($profile) => $profile->where('user_id', $request->user()->id)))->with('session')->latest()->limit(100)->get(),
        ]);
    }

    public function store(MemberProgressRequest $request, Member $member): RedirectResponse
    {
        $this->progressService->record($member, $request->user(), $request->validated());

        return back()->with('success', 'Catatan evaluasi & progres latihan member berhasil disimpan.');
    }

    public function destroy(Request $request, MemberProgressRecord $record): RedirectResponse
    {
        $this->progressService->delete($record, $request->user());

        return back()->with('success', 'Catatan progres berhasil dihapus.');
    }

    public function show(Request $request, Member $member, MemberProgressRecord $record): View
    {
        abort_unless($record->member_id === $member->id, 404);
        abort_unless($record->canBeReadBy($request->user()), 403);

        return view('gym.member-progress-detail', [
            'member' => $member,
            'record' => $record->load('trainer', 'session'),
        ]);
    }
}
