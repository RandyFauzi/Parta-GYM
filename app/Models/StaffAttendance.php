<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_SICK = 'sick';

    public const STATUS_PERMIT = 'permit';

    public const STATUS_ALPHA = 'alpha';

    protected $fillable = [
        'user_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date:Y-m-d',
            'clock_in_at' => 'immutable_datetime',
            'clock_out_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => 'Hadir',
            self::STATUS_LATE => 'Terlambat',
            self::STATUS_SICK => 'Sakit',
            self::STATUS_PERMIT => 'Izin',
            self::STATUS_ALPHA => 'Alpha',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => 'positive',
            self::STATUS_LATE => 'warning',
            self::STATUS_SICK, self::STATUS_PERMIT => 'neutral',
            self::STATUS_ALPHA => 'negative',
            default => 'neutral',
        };
    }

    public function getWorkDurationFormattedAttribute(): ?string
    {
        if (! $this->clock_in_at || ! $this->clock_out_at) {
            return null;
        }

        $minutes = $this->clock_in_at->diffInMinutes($this->clock_out_at);
        $hours = (int) floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes.' menit';
        }

        if ($remainingMinutes === 0) {
            return $hours.' jam';
        }

        return "{$hours} jam {$remainingMinutes} menit";
    }

    public function getWorkDurationHumanAttribute(): string
    {
        return $this->work_duration_formatted ?? '-';
    }
}
