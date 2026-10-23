<?php

namespace App\Services;

use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class TrainerAvailabilityService
{
    public function assertAvailable(TrainerProfile $trainer, CarbonImmutable $start, CarbonImmutable $end, ?string $ignoreSession = null): void
    {
        $start = $start->utc();
        $end = $end->utc();
        $buffer = $trainer->session_buffer_minutes;
        $paddedStart = $start->subMinutes($buffer);
        $paddedEnd = $end->addMinutes($buffer);
        if ($trainer->availability !== null) {
            $covered = false;
            $local = $start->timezone(config('gym.timezone'));
            foreach ([$local->startOfDay()->subDay(), $local->startOfDay()] as $day) {
                foreach ($trainer->availability[(string) $day->dayOfWeek] ?? [] as $slot) {
                    $opens = $day->setTimeFromTimeString($slot['from']);
                    $closes = $day->setTimeFromTimeString($slot['to']);
                    if ($closes->lte($opens)) {
                        $closes = $closes->addDay();
                    }
                    if ($start->gte($opens) && $end->lte($closes)) {
                        $covered = true;
                        break 2;
                    }
                }
            }
            if (! $covered) {
                throw ValidationException::withMessages(['trainer_profile_id' => 'Jadwal berada di luar jam kerja PT.']);
            }
        }
        if ($trainer->timeOffs()->where('starts_at', '<', $paddedEnd)->where('ends_at', '>', $paddedStart)->exists()) {
            throw ValidationException::withMessages(['trainer_profile_id' => 'PT sedang cuti / tidak tersedia pada waktu ini.']);
        }
        if (TrainingSession::where('trainer_profile_id', $trainer->id)->where('status', 'scheduled')
            ->where('starts_at', '<', $paddedEnd)->where('ends_at', '>', $paddedStart)
            ->when($ignoreSession, fn ($q) => $q->whereKeyNot($ignoreSession))->exists()) {
            throw ValidationException::withMessages(['trainer_profile_id' => 'Jadwal PT berbenturan atau jeda antarsesi belum cukup.']);
        }
    }

    /** @return list<array{starts_at: string, ends_at: string}> */
    public function slots(TrainerProfile $trainer, CarbonImmutable $day, int $duration): array
    {
        $slots = [];
        for ($start = $day->startOfDay(); $start->lt($day->addDay()->startOfDay()); $start = $start->addMinutes(30)) {
            if ($start->lte(now())) {
                continue;
            }
            try {
                $end = $start->addMinutes($duration);
                $this->assertAvailable($trainer, $start, $end);
                $slots[] = ['starts_at' => $start->format('Y-m-d H:i'), 'ends_at' => $end->format('Y-m-d H:i')];
            } catch (ValidationException) {
                continue;
            }
        }

        return $slots;
    }
}
