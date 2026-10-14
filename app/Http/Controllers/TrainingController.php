<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainingSessionRequest;
use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\StudioRoom;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Services\MembershipService;
use App\Services\TrainingService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['date' => 'nullable|date_format:Y-m-d', 'activity' => 'nullable|in:gym,pilates', 'status' => 'nullable|in:scheduled,completed,cancelled']);
        $query = TrainingSession::with('trainer.user', 'room')->withCount(['enrollments as occupied' => fn ($q) => $q->whereIn('status', ['confirmed', 'attended', 'no_show'])]);
        if ($request->user()->role === 'personal_trainer') {
            $query->whereHas('trainer', fn ($q) => $q->where('user_id', $request->user()->id));
        }
        if ($request->user()->role === 'member') {
            $query->where('status', 'scheduled')->where('ends_at', '>', now());
        } elseif (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['activity'])) {
            $query->where('activity', $data['activity']);
        }
        if (! empty($data['date'])) {
            $start = CarbonImmutable::parse($data['date'], config('gym.timezone'))->startOfDay();
            $query->where('starts_at', '>=', $start->utc())->where('starts_at', '<', $start->addDay()->utc());
        }

        return view('gym.classes', ['sessions' => $query->orderBy('starts_at')->paginate(12)->withQueryString(), 'rooms' => $request->user()->role === 'owner' ? StudioRoom::orderBy('name')->get() : collect(), 'member' => $request->user()->role === 'member' ? $request->user()->member()->firstOrFail() : null]);
    }

    public function create(Request $request): View
    {
        if ($request->filled('source')) {
            $request->validate(['source' => 'required|uuid|exists:training_sessions,id']);
            $original = TrainingSession::findOrFail($request->input('source'));
            $copy = new TrainingSession($original->only(['title', 'activity', 'kind', 'trainer_profile_id', 'studio_room_id', 'capacity', 'notes']));
            $copy->starts_at = $original->starts_at->addWeek();
            $copy->ends_at = $original->ends_at->addWeek();

            return $this->form($copy);
        }

        return $this->form(new TrainingSession(['activity' => 'pilates', 'kind' => 'group', 'capacity' => 8]));
    }

    public function edit(TrainingSession $session): View
    {
        return $this->form($session);
    }

    private function form(TrainingSession $session): View
    {
        return view('gym.class-form', ['session' => $session, 'trainers' => TrainerProfile::with('user')->whereHas('user', fn ($q) => $q->where('role', 'personal_trainer')->where('is_active', true))->get(), 'rooms' => StudioRoom::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(TrainingSessionRequest $request, TrainingService $service): RedirectResponse
    {
        $session = $service->save($request->validated(), $request->user());

        return redirect()->route('classes.show', $session)->with('success', 'Kelas dijadwalkan.');
    }

    public function update(TrainingSessionRequest $request, TrainingSession $session, TrainingService $service): RedirectResponse
    {
        $service->save($request->validated(), $request->user(), $session);

        return redirect()->route('classes.show', $session)->with('success', 'Kelas diperbarui.');
    }

    private function authorizeSession(Request $request, TrainingSession $session): void
    {
        if ($request->user()->role === 'personal_trainer') {
            abort_unless($session->trainer->user_id === $request->user()->id, 403);
        }
    }

    public function show(Request $request, TrainingSession $session): View
    {
        $this->authorizeSession($request, $session);
        $memberView = $request->user()->role === 'member';
        if ($memberView) {
            abort_unless($session->status === 'scheduled' || $session->enrollments()->whereHas('member', fn ($q) => $q->where('user_id', $request->user()->id))->exists(), 404);
        }
        $enrollments = $session->enrollments()->with('member')->when($memberView, fn ($q) => $q->whereHas('member', fn ($m) => $m->where('user_id', $request->user()->id)))->orderBy('created_at')->get();

        return view('gym.class-detail', ['session' => $session->load('trainer.user', 'room'), 'enrollments' => $enrollments, 'member' => $memberView ? $request->user()->member()->firstOrFail() : null, 'members' => in_array($request->user()->role, ['owner', 'receptionist'], true) ? Member::where('status', 'active')->orderBy('full_name')->get() : collect()]);
    }

    public function enroll(Request $request, TrainingSession $session, TrainingService $service): RedirectResponse
    {
        $data = $request->validate(['member_id' => 'required|uuid|exists:members,id']);
        $service->enroll($session, Member::findOrFail($data['member_id']), $request->user());

        return back()->with('success', 'Peserta terdaftar.');
    }

    public function attendance(Request $request, ClassEnrollment $enrollment, TrainingService $service): RedirectResponse
    {
        $this->authorizeSession($request, $enrollment->session);
        $data = $request->validate(['status' => 'required|in:attended,no_show,cancelled']);
        if ($request->user()->role === 'personal_trainer') {
            abort_if($data['status'] === 'cancelled', 403);
        }
        $service->attendance($enrollment, $data['status'], $request->user());

        return back()->with('success', 'Status peserta diperbarui.');
    }

    public function close(Request $request, TrainingSession $session, TrainingService $service): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:completed,cancelled', 'reason' => 'nullable|required_if:status,cancelled|string|min:5|max:1000', 'confirmed' => 'required|accepted']);
        $service->close($session, $data['status'], $data['reason'] ?? null, $request->user());

        return back()->with('success', 'Status kelas diperbarui dan diaudit.');
    }

    public function room(Request $request, MembershipService $service): RedirectResponse
    {
        $existingRoom = null;
        if ($request->filled('id')) {
            $request->validate(['id' => 'required|uuid|exists:studio_rooms,id']);
            $existingRoom = StudioRoom::findOrFail($request->input('id'));
        }
        $data = $request->validate(['id' => 'nullable|uuid|exists:studio_rooms,id', 'name' => ['required', 'string', 'max:120', Rule::unique('studio_rooms', 'name')->ignore($existingRoom)], 'capacity' => 'required|integer|min:1|max:100', 'is_exclusive' => 'required|boolean', 'is_active' => 'required|boolean']);
        DB::transaction(function () use ($data, $service) {
            if (! empty($data['id'])) {
                $room = StudioRoom::whereKey($data['id'])->lockForUpdate()->firstOrFail();
                if ($room->sessions()->where('status', 'scheduled')->where('ends_at', '>', now())->exists()) {
                    throw ValidationException::withMessages(['room' => 'Ruang masih memiliki jadwal mendatang. Selesaikan atau pindahkan kelas sebelum mengubah ruang.']);
                }
                $room->update(array_diff_key($data, ['id' => true]));
            } else {
                $room = StudioRoom::create($data);
            }
            $service->audit('room.updated', $room->id);
        });

        return back()->with('success', 'Ruang tersimpan.');
    }
}
