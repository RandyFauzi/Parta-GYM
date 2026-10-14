<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPeriod extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['member_id', 'subscription_id', 'source_invoice_id', 'starts_at', 'ends_at', 'status', 'visit_limit'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
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
