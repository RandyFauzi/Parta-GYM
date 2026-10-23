<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainerAssignmentRequest;
use App\Models\Member;
use App\Models\TrainerAssignment;
use App\Models\TrainerProfile;
use App\Services\TrainerAssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainerAssignmentController extends Controller
{
    public function store(TrainerAssignmentRequest $request, Member $member, TrainerAssignmentService $service): RedirectResponse
    {
        $data = $request->validated();
        $service->assign($member, TrainerProfile::findOrFail($data['trainer_profile_id']), $data['activity'],
            empty($data['ends_at']) ? null : CarbonImmutable::parse($data['ends_at'], config('gym.timezone'))->utc(),
            $data['reason'], $request->user());

        return back()->with('success', 'Penugasan PT disimpan dan diaudit.');
    }

    public function destroy(Request $request, TrainerAssignment $assignment, TrainerAssignmentService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('assignments.manage'), 403);
        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);
        $service->revoke($assignment, $data['reason'], $request->user());

        return back()->with('success', 'Penugasan PT dicabut dan diaudit.');
    }
}
