<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['invoice_id', 'staff_shift_id', 'staff_id', 'amount_idr', 'method', 'reference', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'immutable_datetime', 'amount_idr' => 'integer'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
