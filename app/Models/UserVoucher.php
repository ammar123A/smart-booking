<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserVoucher extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'reward_id',
        'loyalty_point_id',
        'code',
        'type',
        'value',
        'status',
        'expires_at',
        'used_at',
        'booking_id',
    ];

    protected $casts = [
        'value' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function loyaltyPoint(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoint::class);
    }

    public function isUsable(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount in cents for a given booking amount.
     * value for discount_percentage is in basis points (e.g. 500 = 5%).
     * value for discount_fixed is in cents (e.g. 2000 = RM 20.00).
     */
    public function calculateDiscount(int $bookingAmount): int
    {
        if ($this->type === 'discount_percentage') {
            return (int) floor($bookingAmount * $this->value / 10000);
        }

        // discount_fixed: cap at booking amount
        return min($this->value, $bookingAmount);
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (self::where('code', $code)->exists());

        return $code;
    }
}
