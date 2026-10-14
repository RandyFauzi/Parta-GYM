<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffShift extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['staff_id', 'active_staff_id', 'opening_cash_idr', 'expected_cash_idr', 'actual_cash_idr', 'cash_difference_idr', 'closed_at'];

    protected function casts(): array
    {
        return ['closed_at' => 'immutable_datetime'];
    }
}
