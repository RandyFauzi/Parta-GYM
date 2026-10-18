<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_MEMBER = 'member';

    public const TYPE_NON_MEMBER = 'non_member';

    public const TYPE_STUDENT = 'student';

    protected $fillable = [
        'user_id',
        'member_number',
        'full_name',
        'phone',
        'email',
        'status',
        'customer_type',
        'student_id_number',
        'notes',
    ];

    public function getCustomerTypeLabelAttribute(): string
    {
        return match ($this->customer_type) {
            self::TYPE_STUDENT => 'Pelajar / Mahasiswa',
            self::TYPE_NON_MEMBER => 'Non-Member / Harian',
            default => 'Member Reguler',
        };
    }

    public function isStudent(): bool
    {
        return $this->customer_type === self::TYPE_STUDENT;
    }

    public function isNonMember(): bool
    {
        return $this->customer_type === self::TYPE_NON_MEMBER;
    }

    public function periods(): HasMany
    {
        return $this->hasMany(MembershipPeriod::class)->withCount(['classEnrollments as reserved_sessions' => fn ($query) => $query->where('status', 'confirmed'), 'classEnrollments as used_sessions' => fn ($query) => $query->whereIn('status', ['attended', 'no_show'])]);
    }

    public function currentPeriod(): HasOne
    {
        return $this->hasOne(MembershipPeriod::class)->whereIn('plan_type', ['gym', 'all_access'])->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())->latest('ends_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function isCoachedBy(User $trainer): bool
    {
        return $this->trainingEnrollments()->whereIn('status', ['confirmed', 'attended', 'no_show'])->whereHas('session.trainer', fn ($query) => $query->where('user_id', $trainer->id))->exists();
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(MemberProgressRecord::class)->latest('recorded_at');
    }
}
