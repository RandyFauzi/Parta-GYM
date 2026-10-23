<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MembershipPeriod;
use Illuminate\Support\Collection;

class MemberDashboardService
{
    /** @return Collection<int, array{period: MembershipPeriod, key: string, label: string, quota: ?int, used: int, reserved: int, remaining: ?int, shared: bool}> */
    public function activeBenefits(Member $member): Collection
    {
        $periods = $member->periods()->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())
            ->with(['invoice', 'entitlements' => fn ($query) => $query->withCount([
                'checkins',
                'enrollments as used_count' => fn ($enrollments) => $enrollments->whereIn('status', ['attended', 'no_show']),
                'enrollments as reserved_count' => fn ($enrollments) => $enrollments->where('status', 'confirmed'),
            ])])->orderBy('ends_at')->orderBy('id')->get();

        return $periods->flatMap(function (MembershipPeriod $period): array {
            $definitions = $period->entitlements->isNotEmpty()
                ? $period->entitlements->pluck('quota', 'benefit_key')->all()
                : ($period->invoice?->snapshot['benefits'] ?? PackageBenefits::legacy($period->plan_type, $period->visit_limit));
            $benefits = [];
            foreach ($definitions as $key => $quota) {
                $entitlement = $period->entitlements->firstWhere('benefit_key', $key);
                if ($entitlement) {
                    $used = $key === 'gym_access' ? $entitlement->checkins_count : $entitlement->used_count;
                    $reserved = $key === 'gym_access' ? 0 : $entitlement->reserved_count;
                } elseif ($key === 'gym_access') {
                    $used = $period->member->checkins()->where('membership_period_id', $period->id)->count();
                    $reserved = 0;
                } else {
                    $enrollments = $period->classEnrollments()->whereHas('session', function ($query) use ($key): void {
                        if ($key === 'legacy_personal') {
                            $query->where('kind', 'personal');
                        } else {
                            [$activity, $kind] = explode('_', $key);
                            $query->where('activity', $activity)->where('kind', $kind);
                        }
                    });
                    $used = (clone $enrollments)->whereIn('status', ['attended', 'no_show'])->count();
                    $reserved = (clone $enrollments)->where('status', 'confirmed')->count();
                }
                $benefits[] = ['period' => $period, 'key' => $key, 'label' => PackageBenefits::LABELS[$key], 'quota' => $quota, 'used' => $used, 'reserved' => $reserved, 'remaining' => $quota === null ? null : max(0, $quota - $used - $reserved), 'shared' => $key === 'legacy_personal'];
            }

            return $benefits;
        })->values();
    }

    /** @param Collection<int, array<string, mixed>> $benefits */
    public function forActivity(Collection $benefits, string $activity): Collection
    {
        return $benefits->filter(fn (array $benefit): bool => $this->matches($benefit['key'], $activity))->values();
    }

    public function matches(string $benefitKey, string $activity): bool
    {
        return $benefitKey === 'legacy_personal' || str_starts_with($benefitKey, $activity.'_');
    }

    public function selectedActivity(Member $member, mixed $preferred, ?Collection $benefits = null): string
    {
        if (in_array($preferred, ['gym', 'pilates'], true)) {
            return $preferred;
        }
        $benefits ??= $this->activeBenefits($member);

        return $this->forActivity($benefits, 'gym')->isEmpty() && $this->forActivity($benefits, 'pilates')->isNotEmpty() ? 'pilates' : 'gym';
    }
}
