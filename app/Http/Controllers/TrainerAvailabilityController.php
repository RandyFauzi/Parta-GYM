<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainerAvailabilityRequest;
use App\Models\TrainerProfile;
use App\Models\TrainerTimeOff;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\TrainerAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainerAvailabilityController extends Controller
{
    public function index(Request $request, TrainerAvailabilityService $service): View
    {
        abort_unless($request->user()->hasPermission('schedule.manage') || $request->user()->role === 'personal_trainer', 403);
        $data = $request->validate(['trainer_id' => 'nullable|uuid|exists:trainer_profiles,id', 'date' => 'nullable|date_format:Y-m-d', 'duration' => 'nullable|integer|min:15|max:180']);
        $trainers = TrainerProfile::with('user')->whereHas('user', fn ($q) => $q->where('role', 'personal_trainer')->where('is_active', true))
            ->when($request->user()->role === 'personal_trainer', fn ($q) => $q->where('user_id', $request->user()->id))->get();
        $trainer = isset($data['trainer_id']) ? $trainers->firstWhere('id', $data['trainer_id']) : $trainers->first();
        abort_if(isset($data['trainer_id']) && ! $trainer, 403);
        $day = CarbonImmutable::parse($data['date'] ?? now()->timezone(config('gym.timezone'))->toDateString(), config('gym.timezone'));
        $duration = (int) ($data['duration'] ?? 60);

        return view('gym.trainer-availability', [
            'trainers' => $trainers, 'trainer' => $trainer?->load('timeOffs'), 'day' => $day, 'duration' => $duration,
            'slots' => $trainer ? $service->slots($trainer, $day, $duration) : [],
        ]);
    }

    public function update(TrainerAvailabilityRequest $request, TrainerProfile $trainer, TrainerAvailabilityService $service): RedirectResponse
    {
        DB::transaction(function () use ($request, $trainer, $service): void {
            User::whereKey($trainer->user_id)->lockForUpdate()->firstOrFail();
            $trainer = TrainerProfile::whereKey($trainer->id)->lockForUpdate()->firstOrFail();
            $trainer->update($request->validated());
            foreach ($trainer->sessions()->where('status', 'scheduled')->where('ends_at', '>', now())->get() as $session) {
                $service->assertAvailable($trainer, $session->starts_at, $session->ends_at, $session->id);
            }
            app(MembershipService::class)->audit('trainer.availability_updated', $trainer->id, $request->validated());
        }, 3);

        return back()->with('success', 'Jam kerja dan jeda PT tersimpan.');
    }

    public function timeOff(Request $request, TrainerProfile $trainer): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('schedule.manage'), 403);
        $data = $request->validate(['starts_at' => 'required|date_format:Y-m-d\TH:i', 'ends_at' => 'required|date_format:Y-m-d\TH:i|after:starts_at', 'reason' => 'required|string|min:5|max:500']);
        $start = CarbonImmutable::parse($data['starts_at'], config('gym.timezone'))->utc();
        $end = CarbonImmutable::parse($data['ends_at'], config('gym.timezone'))->utc();
        DB::transaction(function () use ($trainer, $start, $end, $data, $request): void {
            User::whereKey($trainer->user_id)->lockForUpdate()->firstOrFail();
            $trainer = TrainerProfile::whereKey($trainer->id)->lockForUpdate()->firstOrFail();
            if ($trainer->sessions()->where('status', 'scheduled')->where('starts_at', '<', $end->addMinutes($trainer->session_buffer_minutes))->where('ends_at', '>', $start->subMinutes($trainer->session_buffer_minutes))->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'Pindahkan atau batalkan jadwal PT yang berbenturan sebelum mencatat cuti.']);
            }
            $record = $trainer->timeOffs()->create(['starts_at' => $start, 'ends_at' => $end, 'reason' => $data['reason'], 'created_by' => $request->user()->id]);
            app(MembershipService::class)->audit('trainer.time_off_created', $record->id, $data);
        }, 3);

        return back()->with('success', 'Waktu tidak tersedia dicatat.');
    }

    public function destroy(Request $request, TrainerTimeOff $timeOff): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('schedule.manage'), 403);
        DB::transaction(function () use ($timeOff): void {
            User::whereKey($timeOff->trainer->user_id)->lockForUpdate()->firstOrFail();
            TrainerProfile::whereKey($timeOff->trainer_profile_id)->lockForUpdate()->firstOrFail();
            app(MembershipService::class)->audit('trainer.time_off_deleted', $timeOff->id, $timeOff->only(['reason', 'starts_at', 'ends_at']));
            $timeOff->delete();
        }, 3);

        return back()->with('success', 'Waktu tidak tersedia dihapus dan diaudit.');
    }
}
