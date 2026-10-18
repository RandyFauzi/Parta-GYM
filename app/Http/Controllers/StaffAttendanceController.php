<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffAttendanceController extends Controller
{
    public function __construct(private MembershipService $membershipService) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'owner', 403);

        $request->validate(['date' => 'nullable|date_format:Y-m-d', 'status' => 'nullable|in:present,late,sick,permit,alpha', 'role' => 'nullable|in:owner,receptionist,personal_trainer']);
        $tz = config('gym.timezone', 'Asia/Makassar');
        $date = $request->filled('date') ? $request->date : CarbonImmutable::now($tz)->toDateString();

        $query = StaffAttendance::with('user', 'recorder')
            ->where('date', $date)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', $request->role)));

        $attendances = $query->paginate(20)->withQueryString();

        $eligibleStaff = User::whereIn('role', ['receptionist', 'personal_trainer', 'owner'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $todayAttendances = StaffAttendance::where('date', $date)->get();
        $attendedUserIds = $todayAttendances->pluck('user_id')->all();
        $unrecordedStaff = $eligibleStaff->whereNotIn('id', $attendedUserIds);

        $stats = [
            'total_staff' => $eligibleStaff->count(),
            'present' => $todayAttendances->whereIn('status', ['present', 'late'])->count(),
            'late' => $todayAttendances->where('status', 'late')->count(),
            'sick_or_permit' => $todayAttendances->whereIn('status', ['sick', 'permit'])->count(),
            'alpha' => $todayAttendances->where('status', 'alpha')->count(),
            'not_clocked_in' => $unrecordedStaff->count(),
        ];

        return view('gym.staff-attendance-index', [
            'attendances' => $attendances,
            'stats' => $stats,
            'selectedDate' => $date,
            'eligibleStaff' => $eligibleStaff,
            'unrecordedStaff' => $unrecordedStaff,
        ]);
    }

    public function myAttendance(Request $request): View
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['owner', 'receptionist', 'personal_trainer'], true), 403);

        $tz = config('gym.timezone', 'Asia/Makassar');
        $today = CarbonImmutable::now($tz)->toDateString();
        $todayRecord = $user->todayAttendance;

        $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = $request->filled('month') ? CarbonImmutable::createFromFormat('!Y-m', $request->month, $tz) : CarbonImmutable::now($tz)->startOfMonth();
        $endOfMonth = $month->endOfMonth();

        $history = StaffAttendance::where('user_id', $user->id)
            ->where('date', '>=', $month->toDateString())
            ->where('date', '<=', $endOfMonth->toDateString())
            ->orderByDesc('date')
            ->paginate(15)->withQueryString();

        return view('gym.staff-attendance-me', [
            'user' => $user,
            'todayRecord' => $todayRecord,
            'history' => $history,
            'month' => $month,
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['owner', 'receptionist', 'personal_trainer'], true), 403);

        return DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $today = CarbonImmutable::now(config('gym.timezone'))->toDateString();
            $open = StaffAttendance::where('user_id', $user->id)->whereNotNull('clock_in_at')->whereNull('clock_out_at')->lockForUpdate()->first();
            $existing = StaffAttendance::where('user_id', $user->id)->where('date', $today)->lockForUpdate()->first();
            if ($open || $existing) {
                return back()->with('error', 'Absensi sudah tercatat atau shift sebelumnya belum ditutup. Hubungi owner untuk koreksi.');
            }
            $record = StaffAttendance::create(['user_id' => $user->id, 'date' => $today, 'clock_in_at' => now(), 'status' => 'present', 'recorded_by' => $user->id]);
            $this->membershipService->audit('staff.clock_in', $record->id);

            return back()->with('success', 'Clock In berhasil.');
        }, 3);
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['owner', 'receptionist', 'personal_trainer'], true), 403);

        return DB::transaction(function () use ($user) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $record = StaffAttendance::where('user_id', $user->id)->whereNotNull('clock_in_at')->whereNull('clock_out_at')->orderBy('date')->lockForUpdate()->first();
            if (! $record) {
                return back()->with('error', 'Tidak ada Clock In yang masih terbuka.');
            }
            $record->update(['clock_out_at' => now()]);
            $this->membershipService->audit('staff.clock_out', $record->id);

            return back()->with('success', 'Clock Out berhasil. Total jam kerja: '.$record->work_duration_formatted.'.');
        }, 3);
    }

    public function manualStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'owner', 403);

        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at')->whereIn('role', ['owner', 'receptionist', 'personal_trainer'])],
            'date' => 'required|date_format:Y-m-d|before_or_equal:'.CarbonImmutable::now(config('gym.timezone'))->toDateString(),
            'status' => ['required', Rule::in(['present', 'late', 'sick', 'permit', 'alpha'])],
            'clock_in_time' => 'nullable|date_format:H:i',
            'clock_out_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $tz = config('gym.timezone', 'Asia/Makassar');
        $clockIn = ! empty($data['clock_in_time'])
            ? CarbonImmutable::parse($data['date'].' '.$data['clock_in_time'], $tz)->utc()
            : null;
        $clockOut = ! empty($data['clock_out_time'])
            ? CarbonImmutable::parse($data['date'].' '.$data['clock_out_time'], $tz)->utc()
            : null;

        if ($clockOut && (! $clockIn || $clockOut->lte($clockIn))) {
            throw ValidationException::withMessages(['clock_out_time' => 'Jam keluar harus setelah jam masuk pada hari yang sama.']);
        }
        if (in_array($data['status'], ['sick', 'permit', 'alpha'], true) && ($clockIn || $clockOut)) {
            throw ValidationException::withMessages(['status' => 'Sakit, izin, atau alpha tidak boleh memiliki jam kerja.']);
        }
        if (in_array($data['status'], ['present', 'late'], true) && ! $clockIn) {
            throw ValidationException::withMessages(['clock_in_time' => 'Jam masuk wajib diisi untuk hadir atau terlambat.']);
        }
        if (($clockIn && $clockIn->isFuture()) || ($clockOut && $clockOut->isFuture())) {
            throw ValidationException::withMessages(['clock_in_time' => 'Jam absensi tidak boleh di masa depan.']);
        }
        DB::transaction(function () use ($data, $clockIn, $clockOut, $request) {
            User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            if ($clockIn && StaffAttendance::where('user_id', $data['user_id'])->where('date', '!=', $data['date'])->whereNotNull('clock_in_at')->where('clock_in_at', '<', $clockOut ?? now())->where(fn ($query) => $query->whereNull('clock_out_at')->orWhere('clock_out_at', '>', $clockIn))->exists()) {
                throw ValidationException::withMessages(['clock_in_time' => 'Jam kerja berbenturan dengan catatan absensi lain.']);
            }
            StaffAttendance::updateOrCreate(
                ['user_id' => $data['user_id'], 'date' => $data['date']],
                [
                    'clock_in_at' => $clockIn,
                    'clock_out_at' => $clockOut,
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $request->user()->id,
                ]
            );

            $this->membershipService->audit('staff.attendance_manual', (string) $data['user_id'], [
                'date' => $data['date'],
                'status' => $data['status'],
            ]);

        }, 3);

        return back()->with('success', 'Data absensi karyawan berhasil diperbarui.');
    }

    public function destroy(Request $request, StaffAttendance $attendance): RedirectResponse
    {
        abort_unless($request->user()->role === 'owner', 403);

        DB::transaction(function () use ($attendance) {
            $userId = $attendance->user_id;
            $date = $attendance->date->toDateString();
            $attendance->delete();

            $this->membershipService->audit('staff.attendance_deleted', (string) $userId, ['date' => $date]);

        });

        return back()->with('success', 'Catatan absensi berhasil dihapus.');
    }
}
