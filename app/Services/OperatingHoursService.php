<?php

namespace App\Services;

use App\Models\GymOperatingHour;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class OperatingHoursService
{
    public function checkoutDeadline(CarbonImmutable $instant): ?CarbonImmutable
    {
        if (! GymOperatingHour::exists()) {
            return null;
        }
        $local = $instant->timezone(config('gym.timezone'));
        foreach ([$local->startOfDay()->subDay(), $local->startOfDay()] as $day) {
            $hours = GymOperatingHour::find($day->dayOfWeek);
            if (! $hours || $hours->closed) {
                continue;
            }
            $opens = $day->setTimeFromTimeString($hours->opens_at);
            $closes = $day->setTimeFromTimeString($hours->closes_at);
            if ($closes->lte($opens)) {
                $closes = $closes->addDay();
            }
            if ($local->gte($opens) && $local->lt($closes)) {
                return $closes->utc();
            }
        }
        throw ValidationException::withMessages(['checkin' => 'Gym sedang tutup sesuai jam operasional.']);
    }
}
