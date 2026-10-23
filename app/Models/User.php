<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active || $this->trashed() || ! array_key_exists($permission, config('gym.staff_permissions'))) {
            return false;
        }
        if ($this->role === 'owner') {
            return array_key_exists($permission, config('gym.staff_permissions'));
        }

        return $this->role === 'receptionist' && in_array($permission, $this->permissions ?? ['booking.manage'], true);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class)->latest('date');
    }

    public function todayAttendance(): HasOne
    {
        $today = CarbonImmutable::now(config('gym.timezone'))->toDateString();

        return $this->hasOne(StaffAttendance::class)->where(fn ($query) => $query->where('date', $today)->orWhere(fn ($open) => $open->whereNotNull('clock_in_at')->whereNull('clock_out_at')))->oldest('date');
    }

    protected $attributes = ['role' => 'member', 'is_active' => true];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
