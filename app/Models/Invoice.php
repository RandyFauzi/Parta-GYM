<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['invoice_number', 'member_id', 'subscription_id', 'total_idr', 'snapshot', 'status', 'request_key'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'total_idr' => 'integer'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
