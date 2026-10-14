<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['title', 'activity', 'kind', 'trainer_profile_id', 'studio_room_id', 'starts_at', 'ends_at', 'capacity', 'status', 'notes', 'cancellation_reason', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'capacity' => 'integer'];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class, 'trainer_profile_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(StudioRoom::class, 'studio_room_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }
}
