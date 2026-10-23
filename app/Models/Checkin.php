<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkin extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['member_id', 'membership_entitlement_id', 'membership_period_id', 'staff_id', 'method', 'checked_in_at'];

    protected function casts(): array
    {
        return ['checked_in_at' => 'immutable_datetime'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
