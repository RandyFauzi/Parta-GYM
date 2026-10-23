<?php

namespace App\Models;

use App\Services\PackageBenefits;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_GYM = 'gym';

    public const TYPE_PT = 'pt';

    public const TYPE_PILATES = 'pilates';

    public const TYPE_ALL_ACCESS = 'all_access';

    protected $fillable = [
        'name',
        'plan_type',
        'description',
        'duration_unit',
        'duration_value',
        'visit_limit',
        'price_idr',
        'is_active',
        'benefits',
    ];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'is_active' => 'boolean',
            'price_idr' => 'integer',
            'duration_value' => 'integer',
            'visit_limit' => 'integer',
        ];
    }

    /** @return array<string, int|null> */
    public function getBenefitDefinitionsAttribute(): array
    {
        return array_map(fn (int|string|null $quota): ?int => $quota === null ? null : (int) $quota, $this->benefits ?? PackageBenefits::legacy($this->plan_type, $this->visit_limit));
    }

    public function isPersonalTraining(): bool
    {
        return $this->plan_type === self::TYPE_PT;
    }

    public function getPlanTypeLabelAttribute(): string
    {
        return match ($this->plan_type) {
            self::TYPE_PT => 'Personal Training (PT)',
            self::TYPE_PILATES => 'Pilates Studio',
            self::TYPE_ALL_ACCESS => 'All-Access (Gym + PT/Pilates)',
            default => 'Gym Reguler',
        };
    }
}
