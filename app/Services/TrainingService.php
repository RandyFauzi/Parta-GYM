<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\StudioRoom;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingService
{
    public function save(array $data, User $actor, ?TrainingSession $session = null): TrainingSession
    {
        return DB::transaction(function () use ($data, $actor, $session) {
            $profileIds = collect([$data['trainer_profile_id'], $session?->trainer_profile_id])->filter()->unique()->sort()->values();
            $trainerIds = TrainerProfile::whereIn('id', $profileIds)->pluck('user_id');
            $trainerUsers = User::whereIn('id', $trainerIds)->orderBy('id')->lockForUpdate()->get();
            $profiles = TrainerProfile::whereIn('id', $profileIds)->orderBy('id')->lockForUpdate()->get();
            $roomIds = collect([$data['studio_room_id'], $session?->studio_room_id])->filter()->unique()->sort()->values();
            $rooms = StudioRoom::whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
            $profile = $profiles->firstWhere('id', $data['trainer_profile_id']);
            $trainer = $trainerUsers->firstWhere('id', $profile?->user_id);
            $room = $rooms->firstWhere('id', $data['studio_room_id']);
            abort_unless($actor->role === 'owner' || ($actor->role === 'personal_trainer' && $profile?->user_id === $actor->id && (! $session || $session->trainer_profile_id === $profile->id)), 403);
            if (! $trainer || ! $trainer->is_active || $trainer->role !== 'personal_trainer') {
                throw ValidationException::withMessages(['trainer_profile_id' => 'Pilih trainer yang aktif.']);
            }
            if (! $room || ! $room->is_active || $data['capacity'] > $room->capacity || ($data['kind'] === 'personal' && (int) $data['capacity'] !== 1)) {
                throw ValidationException::withMessages(['capacity' => 'Kapasitas harus sesuai ruang. Sesi personal berkapasitas satu anggota.']);
            }
            $start = CarbonImmutable::parse($data['starts_at'], config('gym.timezone'))->utc();
            $end = CarbonImmutable::parse($data['ends_at'], config('gym.timezone'))->utc();
            if ($session) {
                $current = TrainingSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
                if ($current->trainer_profile_id !== $session->trainer_profile_id || $current->studio_room_id !== $session->studio_room_id) {
                    throw ValidationException::withMessages(['title' => 'Jadwal telah diperbarui. Muat ulang sebelum mengedit.']);
                }
                if ($current->status !== 'scheduled' || $current->starts_at->lte(now())) {
                    throw ValidationException::withMessages(['title' => 'Jadwal yang telah dimulai atau ditutup tidak dapat diubah.']);
                }
                $filled = $current->enrollments()->whereIn('status', ['confirmed', 'attended', 'no_show'])->count();
                if ($data['capacity'] < $filled) {
                    throw ValidationException::withMessages(['capacity' => 'Kapasitas tidak boleh lebih kecil dari jumlah peserta.']);
                }
                if ($filled > 0 && (! $current->starts_at->equalTo($start) || ! $current->ends_at->equalTo($end))) {
                    throw ValidationException::withMessages(['starts_at' => 'Sesi sudah memiliki peserta. Batalkan sesi dengan alasan dan buat jadwal baru untuk perubahan waktu.']);
                }
            }
            if ($start->lte(now()) || $end->lte($start) || $start->diffInHours($end) > 12) {
                throw ValidationException::withMessages(['starts_at' => 'Jadwal harus dimulai di masa depan dan durasi maksimal 12 jam.']);
            }
            $overlaps = TrainingSession::where('status', 'scheduled')->where('starts_at', '<', $end)->where('ends_at', '>', $start)->when($session, fn ($q) => $q->whereKeyNot($session->id));
            if ((clone $overlaps)->where('trainer_profile_id', $profile->id)->exists()) {
                throw ValidationException::withMessages(['trainer_profile_id' => 'Trainer sudah memiliki sesi pada waktu ini.']);
            }
            if ($room->is_exclusive && (clone $overlaps)->where('studio_room_id', $room->id)->exists()) {
                throw ValidationException::withMessages(['studio_room_id' => 'Ruang sudah digunakan pada waktu ini.']);
            }
            if (! $room->is_exclusive && (clone $overlaps)->where('studio_room_id', $room->id)->sum('capacity') + (int) $data['capacity'] > $room->capacity) {
                throw ValidationException::withMessages(['capacity' => 'Total kapasitas sesi bersamaan melebihi kapasitas ruang bersama.']);
            }
            $attributes = [...$data, 'starts_at' => $start, 'ends_at' => $end];
            if ($session) {
                $current->update($attributes);
                $saved = $current;
            } else {
                $saved = TrainingSession::create([...$attributes, 'created_by' => $actor->id, 'status' => 'scheduled']);
            }
            app(MembershipService::class)->audit($session ? 'class.updated' : 'class.created', $saved->id);

            return $saved;
        }, 3);
    }

    public function enroll(TrainingSession $session, Member $member, User $actor): void
    {
        DB::transaction(function () use ($session, $member, $actor) {
            abort_unless(in_array($actor->role, ['owner', 'receptionist'], true) || ($actor->role === 'member' && $member->user_id === $actor->id), 403);
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $session = TrainingSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($member->status !== 'active' || $session->status !== 'scheduled' || $session->starts_at->lte(now())) {
                throw ValidationException::withMessages(['member_id' => 'Anggota harus aktif dan kelas belum dimulai.']);
            }
            $enrollment = ClassEnrollment::where('training_session_id', $session->id)->where('member_id', $member->id)->first();
            if ($enrollment && $enrollment->status !== 'cancelled') {
                return;
            }
            if ($session->enrollments()->whereIn('status', ['confirmed', 'attended', 'no_show'])->count() >= $session->capacity) {
                throw ValidationException::withMessages(['member_id' => 'Kelas sudah penuh.']);
            }
            $conflict = ClassEnrollment::where('member_id', $member->id)->where('status', 'confirmed')->whereHas('session', fn ($q) => $q->where('status', 'scheduled')->where('starts_at', '<', $session->ends_at)->where('ends_at', '>', $session->starts_at))->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['member_id' => 'Anggota sudah terdaftar pada sesi lain di waktu ini.']);
            }
            $enrollment = ClassEnrollment::updateOrCreate(['training_session_id' => $session->id, 'member_id' => $member->id], ['created_by' => $actor->id, 'status' => 'confirmed', 'attended_at' => null, 'recorded_by' => null]);
            app(MembershipService::class)->audit('class.enrolled', $enrollment->id);
        }, 3);
    }

    public function attendance(ClassEnrollment $enrollment, string $status, User $actor): void
    {
        DB::transaction(function () use ($enrollment, $status, $actor) {
            $session = TrainingSession::whereKey($enrollment->training_session_id)->lockForUpdate()->firstOrFail();
            $enrollment = ClassEnrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($enrollment->status === $status) {
                return;
            }
            if ($session->status !== 'scheduled' || $enrollment->status !== 'confirmed') {
                throw ValidationException::withMessages(['status' => 'Peserta atau sesi sudah ditutup.']);
            }
            if ($status !== 'cancelled' && (now()->lt($session->starts_at->subMinutes(30)) || ($status === 'no_show' && now()->lt($session->ends_at)))) {
                throw ValidationException::withMessages(['status' => 'Absensi dibuka 30 menit sebelum sesi. Tidak hadir dicatat setelah sesi berakhir.']);
            }
            $enrollment->update(['status' => $status, 'recorded_by' => $actor->id, 'attended_at' => $status === 'attended' ? now() : null]);
            app(MembershipService::class)->audit('class.attendance', $enrollment->id, ['status' => $status]);
        }, 3);
    }

    public function close(TrainingSession $session, string $status, ?string $reason, User $actor): void
    {
        DB::transaction(function () use ($session, $status, $reason, $actor) {
            $session = TrainingSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->role === 'owner' || ($actor->role === 'personal_trainer' && $session->trainer->user_id === $actor->id), 403);
            if ($session->status !== 'scheduled') {
                return;
            }
            if ($status === 'completed' && now()->lt($session->ends_at)) {
                throw ValidationException::withMessages(['status' => 'Kelas hanya bisa diselesaikan setelah waktu berakhir.']);
            }
            $session->enrollments()->where('status', 'confirmed')->update(['status' => $status === 'cancelled' ? 'cancelled' : 'no_show', 'recorded_by' => $actor->id, 'updated_at' => now()]);
            $session->update(['status' => $status, 'cancellation_reason' => $status === 'cancelled' ? $reason : null]);
            app(MembershipService::class)->audit('class.'.$status, $session->id, ['reason' => $reason]);
        }, 3);
    }
}
