<?php

namespace App\Models;

use App\Services\PackageBenefits;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipEntitlement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['membership_period_id', 'benefit_key', 'quota'];

    protected function casts(): array
    {
        return ['quota' => 'integer'];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(MembershipPeriod::class, 'membership_period_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function getLabelAttribute(): string
    {
        return PackageBenefits::LABELS[$this->benefit_key];
    }

    public function getReservedAttribute(): int
    {
        return $this->enrollments()->where('status', 'confirmed')->count();
    }

    public function getUsedAttribute(): int
    {
        return $this->benefit_key === 'gym_access'
            ? $this->checkins()->count()
            : $this->enrollments()->whereIn('status', ['attended', 'no_show'])->count();
    }

    public function getRemainingAttribute(): ?int
    {
        if ($this->quota === null) {
            return null;
        }
        $allocated = $this->benefit_key === 'gym_access'
            ? $this->checkins()->count()
            : $this->enrollments()->whereIn('status', ['confirmed', 'attended', 'no_show'])->count();

        return max(0, $this->quota - $allocated);
    }
}
