<?php

namespace App\Services;

use App\Models\Checkin;
use App\Models\ClassEnrollment;
use App\Models\MembershipPeriod;

class PackageBenefits
{
    public const LABELS = [
        'gym_access' => 'Akses Gym',
        'gym_group' => 'Gym grup',
        'gym_personal' => 'Gym privat / PT',
        'pilates_group' => 'Pilates grup',
        'pilates_personal' => 'Pilates privat',
        'legacy_personal' => 'Personal Gym/Pilates (paket lama)',
    ];

    /** @return array<string, int|null> */
    public static function legacy(string $type, ?int $quota): array
    {
        return match ($type) {
            'pt' => ['legacy_personal' => $quota],
            'pilates' => ['pilates_group' => $quota],
            'all_access' => ['gym_access' => $quota, 'legacy_personal' => $quota],
            default => ['gym_access' => $quota],
        };
    }

    /** @param array<string, int|null> $benefits */
    public function initialize(MembershipPeriod $period, array $benefits): void
    {
        foreach ($benefits as $key => $quota) {
            $entitlement = $period->entitlements()->firstOrCreate(['benefit_key' => $key], ['quota' => $quota]);
            if ($key === 'gym_access') {
                Checkin::where('membership_period_id', $period->id)->whereNull('membership_entitlement_id')->update(['membership_entitlement_id' => $entitlement->id]);
            } else {
                ClassEnrollment::where('membership_period_id', $period->id)->whereNull('membership_entitlement_id')
                    ->whereHas('session', function ($query) use ($key) {
                        if ($key === 'legacy_personal') {
                            $query->where('kind', 'personal');
                        } else {
                            [$activity, $kind] = explode('_', $key);
                            $query->where('activity', $activity)->where('kind', $kind);
                        }
                    })->update(['membership_entitlement_id' => $entitlement->id]);
            }
        }
    }
}
