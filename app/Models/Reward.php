<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'type',
        'value',
        'service_id',
        'max_redemptions',
        'times_redeemed',
        'min_tier_id',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'value' => 'integer',
        'max_redemptions' => 'integer',
        'times_redeemed' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function minTier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'min_tier_id');
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_redemptions && $this->times_redeemed >= $this->max_redemptions) {
            return false;
        }

        $now = now()->toDateString();

        if ($this->valid_from && $now < $this->valid_from->toDateString()) {
            return false;
        }

        if ($this->valid_until && $now > $this->valid_until->toDateString()) {
            return false;
        }

        return true;
    }

    public function canBeRedeemedBy(User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($user->loyalty_points < $this->points_cost) {
            return false;
        }

        if ($this->min_tier_id && (!$user->loyaltyTier || $user->loyaltyTier->min_points < $this->minTier->min_points)) {
            return false;
        }

        return true;
    }
}
