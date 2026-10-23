<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberProgressRecord;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberProgressService
{
    /** @param array<string, mixed> $data */
    public function record(Member $member, User $actor, array $data): MemberProgressRecord
    {
        return DB::transaction(function () use ($member, $actor, $data): MemberProgressRecord {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless($actor->is_active && ($actor->role === 'owner' || $member->isCoachedBy($actor)), 403);
            $session = empty($data['training_session_id']) ? null : TrainingSession::whereKey($data['training_session_id'])->lockForUpdate()->firstOrFail();
            $activities = $member->trainerAssignments()->active()->whereHas('trainer', fn ($query) => $query->where('user_id', $actor->id))->pluck('activity');
            $activity = $data['activity'] ?? $session?->activity ?? ($activities->count() === 1 ? $activities->first() : null);
            if ($activity === null && $actor->role === 'personal_trainer') {
                throw ValidationException::withMessages(['activity' => 'Pilih aktivitas sesuai penugasan PT.']);
            }
            abort_unless($actor->role === 'owner' || $member->isCoachedBy($actor, $activity), 403);
            if ($session) {
                abort_unless($actor->role === 'owner' || $session->trainer?->user_id === $actor->id, 403);
                if ($activity !== $session->activity || $session->status === 'cancelled' || $session->starts_at->isFuture() || ! $member->trainingEnrollments()->where('training_session_id', $session->id)->whereIn('status', ['confirmed', 'attended', 'no_show'])->exists()) {
                    throw ValidationException::withMessages(['training_session_id' => 'Pilih sesi member yang sudah dimulai dan sesuai aktivitas laporan.']);
                }
            }
            if (! empty($data['request_key'])) {
                $existing = MemberProgressRecord::where('request_key', $data['request_key'])->first();
                if ($existing) {
                    abort_unless($existing->member_id === $member->id && $existing->trainer_id === $actor->id, 403);

                    return $existing;
                }
            }
            $record = $member->progressRecords()->create(array_merge($data, ['trainer_id' => $actor->id, 'activity' => $activity, 'recorded_at' => CarbonImmutable::parse($data['recorded_at'], config('gym.timezone'))->utc()]));
            app(MembershipService::class)->audit('member_progress.recorded', $record->id, ['member_id' => $member->id, 'trainer_id' => $actor->id, 'activity' => $activity]);

            return $record;
        }, 3);
    }

    public function delete(MemberProgressRecord $record, User $actor): void
    {
        DB::transaction(function () use ($record, $actor): void {
            Member::whereKey($record->member_id)->lockForUpdate()->firstOrFail();
            $actor = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $record = MemberProgressRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->canBeReadBy($actor) && ($actor->role === 'owner' || ($actor->role === 'personal_trainer' && $record->trainer_id === $actor->id)), 403);
            app(MembershipService::class)->audit('member_progress.deleted', $record->id, ['member_id' => $record->member_id, 'record' => $record->toArray()]);
            $record->delete();
        }, 3);
    }

    /** @return array<string, array{first: float, latest: float, change: float, from: mixed, to: mixed}> */
    public function measurements(Builder|HasMany $query): array
    {
        $summary = [];
        foreach (['weight_kg', 'body_fat_percentage', 'muscle_mass_kg', 'waist_cm'] as $metric) {
            $measured = (clone $query)->reorder()->whereNotNull($metric);
            $first = (clone $measured)->orderBy('recorded_at')->orderBy('created_at')->orderBy('id')->first();
            $latest = (clone $measured)->orderByDesc('recorded_at')->orderByDesc('created_at')->orderByDesc('id')->first();
            if ($first && $latest) {
                $summary[$metric] = ['first' => $first->$metric, 'latest' => $latest->$metric, 'change' => round($latest->$metric - $first->$metric, 2), 'from' => $first->recorded_at, 'to' => $latest->recorded_at];
            }
        }

        return $summary;
    }
}
