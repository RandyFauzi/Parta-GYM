<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymOperatingHour extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = ['id', 'closed', 'opens_at', 'closes_at'];

    protected function casts(): array
    {
        return ['closed' => 'boolean'];
    }
}
