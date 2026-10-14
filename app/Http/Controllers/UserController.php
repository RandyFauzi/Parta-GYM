<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAccountRequest;
use App\Models\Member;
use App\Models\StaffShift;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|in:owner,receptionist,personal_trainer,member', 'status' => 'nullable|in:active,inactive,archived']);
        $trainerPage = $request->routeIs('trainers');
        $query = User::with('trainerProfile');
        if (($filters['status'] ?? '') === 'archived') {
            $query->onlyTrashed();
        }
        if ($trainerPage) {
            $query->where('role', 'personal_trainer');
        } elseif (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        if (in_array($filters['status'] ?? '', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }

        return view('gym.users', ['accounts' => $query->orderBy('name')->paginate(15)->withQueryString(), 'trainerPage' => $trainerPage]);
    }

    public function create(Request $request): View
    {
        return view('gym.user-form', ['account' => new User(['role' => $request->query('role') === 'personal_trainer' ? 'personal_trainer' : 'receptionist']), 'members' => Member::whereNull('user_id')->orderBy('full_name')->get()]);
    }

    public function edit(User $user): View
    {
        return view('gym.user-form', ['account' => $user->load('member', 'trainerProfile'), 'members' => Member::whereNull('user_id')->orWhere('user_id', $user->id)->orderBy('full_name')->get()]);
    }

    public function store(UserAccountRequest $request, MembershipService $service): RedirectResponse
    {
        $account = DB::transaction(function () use ($request, $service) {
            User::where('role', 'owner')->orderBy('id')->lockForUpdate()->get();
            $user = new User;
            $data = $request->validated();
            $user->forceFill([...array_intersect_key($data, array_flip(['name', 'email', 'role', 'is_active', 'password'])), 'remember_token' => Str::random(60)])->save();
            $this->linkProfiles($user, $data);
            $service->audit('user.created', (string) $user->id, ['role' => $user->role]);

            return $user;
        });

        return redirect()->route('users.show', $account)->with('success', 'Akun dibuat. Sampaikan akses secara pribadi.');
    }

    public function update(UserAccountRequest $request, User $user, MembershipService $service): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $service) {
            $owners = User::where('role', 'owner')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            $losingAccess = $data['role'] !== $account->role || ! (bool) $data['is_active'];
            if ($request->user()->is($account) && $losingAccess) {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat menghapus atau menurunkan akses akun sendiri.']);
            }
            if ($account->role === 'owner' && $account->is_active && ($data['role'] !== 'owner' || ! (bool) $data['is_active']) && $owners->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Minimal satu owner aktif harus dipertahankan.']);
            }
            if ($losingAccess) {
                $this->ensureNoOpenShift($account);
            }
            $securityChanged = $account->role !== $data['role'] || $account->email !== $data['email'] || $account->is_active !== (bool) $data['is_active'] || ! empty($data['password']);
            if (empty($data['password'])) {
                unset($data['password']);
            }
            if ($account->email !== $data['email']) {
                $account->email_verified_at = null;
            }
            $account->forceFill(array_intersect_key($data, array_flip(['name', 'email', 'role', 'is_active', 'password'])))->save();
            $this->linkProfiles($account, $data);
            if ($securityChanged) {
                $account->forceFill(['remember_token' => Str::random(60)])->save();
                $sessions = DB::table('sessions')->where('user_id', $account->id);
                if ($request->user()->is($account)) {
                    $sessions->where('id', '!=', $request->session()->getId());
                }
                $sessions->delete();
            }
            $service->audit('user.updated', (string) $account->id, ['role' => $account->role, 'is_active' => $account->is_active, 'password_reset' => ! empty($data['password'])]);
        });

        return redirect()->route('users.show', $user)->with('success', 'Akun diperbarui. Perubahan akses membatalkan sesi lama.');
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($request->user()->role === 'owner' || $request->user()->is($user), 403);

        return view('gym.user-profile', ['account' => $user->load('member', 'trainerProfile')]);
    }

    public function account(Request $request): View
    {
        return $this->show($request, $request->user());
    }

    public function destroy(Request $request, User $user, MembershipService $service): RedirectResponse
    {
        $request->validate(['confirmed' => 'required|accepted']);
        DB::transaction(function () use ($request, $user, $service) {
            $owners = User::where('role', 'owner')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($request->user()->is($account), 403);
            if ($account->role === 'owner' && $account->is_active && $owners->count() <= 1) {
                throw ValidationException::withMessages(['user' => 'Owner aktif terakhir tidak boleh dihapus.']);
            }
            $this->ensureNoOpenShift($account);
            $account->forceFill(['is_active' => false, 'remember_token' => null])->save();
            $service->audit('user.archived', (string) $account->id);
            DB::table('sessions')->where('user_id', $account->id)->delete();
            $account->delete();
        });

        return redirect()->route('users.index')->with('success', 'Akun diarsipkan. Riwayat transaksi tetap tersimpan.');
    }

    public function restore(Request $request, string $id, MembershipService $service): RedirectResponse
    {
        $request->validate(['confirmed' => 'required|accepted']);
        DB::transaction(function () use ($id, $service) {
            $user = User::onlyTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
            $user->restore();
            $user->forceFill(['is_active' => false])->save();
            $service->audit('user.restored', (string) $user->id);
        });

        return redirect()->route('users.edit', $id)->with('success', 'Akun dipulihkan dalam kondisi nonaktif. Periksa akses sebelum mengaktifkan.');
    }

    private function ensureNoOpenShift(User $user): void
    {
        if (TrainingSession::whereHas('trainer', fn ($q) => $q->where('user_id', $user->id))->where('status', 'scheduled')->where('ends_at', '>', now())->exists()) {
            throw ValidationException::withMessages(['user' => 'Trainer masih memiliki kelas. Alihkan trainer atau batalkan jadwal terlebih dahulu.']);
        }
        if (StaffShift::where('active_staff_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['user' => 'Tutup shift kasir akun ini terlebih dahulu.']);
        }
    }

    private function linkProfiles(User $user, array $data): void
    {
        if ($user->role === 'member') {
            $member = Member::whereKey($data['member_id'])->lockForUpdate()->firstOrFail();
            if (($member->user_id && $member->user_id !== $user->id) || Member::where('user_id', $user->id)->whereKeyNot($member->id)->exists()) {
                throw ValidationException::withMessages(['member_id' => 'Profil anggota sudah terhubung ke akun lain atau akun ini sudah memiliki profil.']);
            }
            $member->update(['user_id' => $user->id]);
        }
        if ($user->role === 'personal_trainer') {
            $user->trainerProfile()->updateOrCreate(['user_id' => $user->id], array_intersect_key($data, array_flip(['bio', 'specialization', 'qualifications', 'experience_years'])));
        }
    }
}
