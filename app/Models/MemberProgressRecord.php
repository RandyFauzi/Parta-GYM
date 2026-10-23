<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProgressRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'member_id',
        'trainer_id',
        'training_session_id',
        'recorded_at',
        'weight_kg',
        'height_cm',
        'body_fat_percentage',
        'muscle_mass_kg',
        'chest_cm',
        'waist_cm',
        'hips_cm',
        'arms_cm',
        'workout_summary',
        'diet_summary',
        'coach_notes',
        'next_goals',
        'activity',
        'program_name',
        'exercises',
        'request_key',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'immutable_datetime',
            'exercises' => 'array',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'body_fat_percentage' => 'float',
            'muscle_mass_kg' => 'float',
            'chest_cm' => 'float',
            'waist_cm' => 'float',
            'hips_cm' => 'float',
            'arms_cm' => 'float',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeVisibleToTrainer(Builder $query, User $trainer, Member $member): Builder
    {
        $activities = $member->trainerAssignments()->active()->whereHas('trainer', fn ($profile) => $profile->where('user_id', $trainer->id))->pluck('activity');

        return $query->where(function (Builder $records) use ($activities): void {
            $records->whereIn('activity', $activities);
            if ($activities->isNotEmpty()) {
                $records->orWhere(fn (Builder $legacy) => $legacy->whereNull('activity')->where(fn (Builder $session) => $session->whereNull('training_session_id')->orWhereHas('session', fn ($session) => $session->whereIn('activity', $activities))));
            }
        });
    }

    public function canBeReadBy(User $user): bool
    {
        if (! $user->is_active || $user->trashed()) {
            return false;
        }

        return $user->role === 'owner'
            || ($user->role === 'member' && $this->member->user_id === $user->id)
            || ($user->role === 'personal_trainer' && self::whereKey($this->id)->visibleToTrainer($user, $this->member)->exists());
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id')->withTrashed();
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function getBmiAttribute(): ?float
    {
        if (! $this->weight_kg || ! $this->height_cm || $this->height_cm <= 0) {
            return null;
        }

        $heightInMeters = $this->height_cm / 100;

        return round($this->weight_kg / ($heightInMeters * $heightInMeters), 1);
    }

    public function getBmiCategoryAttribute(): ?string
    {
        $bmi = $this->bmi;

        if ($bmi === null) {
            return null;
        }

        return match (true) {
            $bmi < 18.5 => 'Kurang (Underweight)',
            $bmi < 25.0 => 'Ideal (Normal)',
            $bmi < 30.0 => 'Berlebih (Overweight)',
            default => 'Obesitas',
        };
    }
}
