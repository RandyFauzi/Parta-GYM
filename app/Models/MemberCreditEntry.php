<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberCreditEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['member_id', 'membership_change_id', 'amount_idr'];

    protected function casts(): array
    {
        return ['amount_idr' => 'integer'];
    }
}
