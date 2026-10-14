<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRequest;
use App\Models\Member;
use App\Models\StaffShift;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('gym.staff', ['staff' => User::whereIn('role', ['owner', 'receptionist'])->get()]);
    }

    public function store(StaffRequest $r, MembershipService $service): RedirectResponse
    {
        DB::transaction(function () use ($r, $service) {
            $user = User::create($r->safe()->only(['name', 'email', 'password']));
            $user->role = 'receptionist';
            $user->save();
            $service->audit('staff.created', (string) $user->id);
        });

        return back()->with('success', 'Akun resepsionis dibuat. Berikan akses secara pribadi.');
    }

    public function toggle(Request $r, User $user, MembershipService $service): RedirectResponse
    {
        abort_unless($user->role === 'receptionist', 403);
        DB::transaction(function () use ($user, $service) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->is_active && StaffShift::where('active_staff_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user' => 'Tutup shift kasir terlebih dahulu.']);
            }
            $user->is_active = ! $user->is_active;
            $user->remember_token = null;
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->save();
            $service->audit('staff.access_changed', (string) $user->id, ['is_active' => $user->is_active]);
        });

        return back()->with('success', 'Akses staf diperbarui.');
    }

    public function status(Request $r, Member $member, MembershipService $service): RedirectResponse
    {
        $data = $r->validate(['status' => 'required|in:active,suspended', 'reason' => 'required|string|min:5|max:500']);
        DB::transaction(function () use ($member, $data, $service) {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $previous = $member->status;
            $member->update(['status' => $data['status']]);
            $service->audit('member.access_changed', $member->id, ['previous' => $previous, 'new' => $data['status'], 'reason' => $data['reason']]);
        });

        return back()->with('success', 'Status akses anggota diperbarui dan diaudit.');
    }
}
