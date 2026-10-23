<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainerProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'bio', 'specialization', 'qualifications', 'experience_years', 'availability', 'session_buffer_minutes'];

    protected function casts(): array
    {
        return ['availability' => 'array', 'session_buffer_minutes' => 'integer'];
    }

    public function timeOffs(): HasMany
    {
        return $this->hasMany(TrainerTimeOff::class)->orderBy('starts_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
}
