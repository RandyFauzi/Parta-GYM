<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudioRoom extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'capacity', 'is_exclusive', 'is_active'];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_exclusive' => 'boolean', 'is_active' => 'boolean'];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
}
