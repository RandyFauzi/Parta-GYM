<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Member;
use App\Models\TrainerAssignment;
use App\Models\TrainerProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainerAssignmentService
{
    public function assign(Member $member, TrainerProfile $trainer, string $activity, ?CarbonImmutable $endsAt, string $reason, User $actor): TrainerAssignment
    {
        abort_unless($actor->hasPermission('assignments.manage'), 403);

        return DB::transaction(function () use ($member, $trainer, $activity, $endsAt, $reason, $actor) {
            Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $trainerUser = User::whereKey($trainer->user_id)->lockForUpdate()->first();
            if (! $trainerUser || ! $trainerUser->is_active || $trainerUser->role !== 'personal_trainer') {
                throw ValidationException::withMessages(['trainer_profile_id' => 'Pilih PT yang aktif.']);
            }
            $current = $member->trainerAssignments()->where('active_key', $member->id.':'.$activity)->lockForUpdate()->first();
            if ($this->hasConflictingBookings($member, $activity, $trainer->id, $endsAt)) {
                throw ValidationException::withMessages(['trainer_profile_id' => 'Masih ada booking personal dengan PT lain atau di luar masa penugasan. Batalkan atau pindahkan booking terlebih dahulu.']);
            }
            if ($current && $current->trainer_profile_id === $trainer->id && $current->ends_at?->toIso8601String() === $endsAt?->toIso8601String()) {
                return $current;
            }
            if ($current) {
                $current->update(['revoked_at' => now(), 'active_key' => null]);
            }
            $assignment = $member->trainerAssignments()->create([
                'trainer_profile_id' => $trainer->id, 'activity' => $activity, 'starts_at' => now(),
                'ends_at' => $endsAt, 'assigned_by' => $actor->id, 'active_key' => $member->id.':'.$activity,
                'reason' => $reason, 'source' => 'management',
            ]);
            app(MembershipService::class)->audit('trainer.assigned', $assignment->id, ['member_id' => $member->id, 'trainer_profile_id' => $trainer->id, 'activity' => $activity, 'reason' => $reason]);

            return $assignment;
        }, 3);
    }

    public function revoke(TrainerAssignment $assignment, string $reason, User $actor): void
    {
        abort_unless($actor->hasPermission('assignments.manage'), 403);
        DB::transaction(function () use ($assignment, $reason) {
            Member::whereKey($assignment->member_id)->lockForUpdate()->firstOrFail();
            $assignment = TrainerAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ($assignment->revoked_at) {
                return;
            }
            if ($this->hasConflictingBookings($assignment->member, $assignment->activity, null, null)) {
                throw ValidationException::withMessages(['assignment' => 'Batalkan atau pindahkan booking personal yang masih terbuka sebelum mencabut penugasan.']);
            }
            $assignment->update(['revoked_at' => now(), 'active_key' => null]);
            app(MembershipService::class)->audit('trainer.revoked', $assignment->id, ['reason' => $reason]);
        }, 3);
    }

    private function hasConflictingBookings(Member $member, string $activity, ?string $trainerId, ?CarbonImmutable $endsAt): bool
    {
        return ClassEnrollment::where('member_id', $member->id)->where('status', 'confirmed')
            ->whereHas('session', function ($query) use ($activity, $trainerId, $endsAt) {
                $query->where('activity', $activity)->where('kind', 'personal')->where('status', 'scheduled')->where('ends_at', '>', now());
                if ($trainerId) {
                    $query->where(fn ($q) => $q->where('trainer_profile_id', '!=', $trainerId)->when($endsAt, fn ($end) => $end->orWhere('ends_at', '>', $endsAt)));
                }
            })->exists();
    }
}
