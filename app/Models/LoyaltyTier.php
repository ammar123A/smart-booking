<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'min_points',
        'discount_percentage',
        'points_multiplier',
        'color',
        'icon',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'discount_percentage' => 'decimal:2',
        'points_multiplier' => 'decimal:2',
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'min_tier_id');
    }
}
