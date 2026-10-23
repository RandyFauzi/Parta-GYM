<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipChange extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['member_id', 'source_period_id', 'invoice_id', 'requested_by', 'source_credit_idr', 'applied_credit_idr', 'amount_due_idr', 'status', 'reason', 'source_snapshot', 'request_key', 'activated_at'];

    protected function casts(): array
    {
        return ['source_credit_idr' => 'integer', 'applied_credit_idr' => 'integer', 'amount_due_idr' => 'integer', 'source_snapshot' => 'array', 'activated_at' => 'immutable_datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sourcePeriod(): BelongsTo
    {
        return $this->belongsTo(MembershipPeriod::class, 'source_period_id');
    }
}
