<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipPeriod extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['member_id', 'subscription_id', 'source_invoice_id', 'starts_at', 'ends_at', 'status', 'visit_limit', 'plan_type'];

    protected function casts(): array
    {
        return ['visit_limit' => 'integer', 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(MembershipEntitlement::class);
    }

    public function getGymQuotaAttribute(): ?int
    {
        $entitlement = $this->entitlements->firstWhere('benefit_key', 'gym_access');

        return $entitlement ? $entitlement->quota : $this->visit_limit;
    }

    public function classEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'source_invoice_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
