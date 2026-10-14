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

    protected $fillable = ['user_id', 'member_number', 'full_name', 'phone', 'email', 'status'];

    public function periods(): HasMany
    {
        return $this->hasMany(MembershipPeriod::class);
    }

    public function currentPeriod(): HasOne
    {
        return $this->hasOne(MembershipPeriod::class)->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())->latest('ends_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }
}
