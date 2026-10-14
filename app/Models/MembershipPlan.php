<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'description', 'duration_unit', 'duration_value', 'visit_limit', 'price_idr', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price_idr' => 'integer', 'duration_value' => 'integer', 'visit_limit' => 'integer'];
    }
}
