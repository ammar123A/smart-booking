<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyTier;
use App\Models\Reward;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Award points to user for completing a booking
     */
    public function awardPoints(User $user, Booking $booking): LoyaltyPoint
    {
        $basePoints = $this->calculateBasePoints($booking);
        $multiplier = $user->loyaltyTier?->points_multiplier ?? 1.0;
        $points = (int) round($basePoints * $multiplier);

        return DB::transaction(function () use ($user, $booking, $points) {
            $newBalance = $user->loyalty_points + $points;
            
            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'points' => $points,
                'type' => 'earned',
                'description' => "Earned from booking #{$booking->id}",
                'balance_after' => $newBalance,
                'expires_at' => now()->addYear(), // Points expire after 1 year
            ]);

            $user->update(['loyalty_points' => $newBalance]);

            // Check for tier upgrade
            $this->checkAndUpgradeTier($user);

            return $loyaltyPoint;
        });
    }

    /**
     * Calculate base points from booking amount
     * 1 point per RM 1 spent (amount is in cents, so divide by 100)
     */
    protected function calculateBasePoints(Booking $booking): int
    {
        return (int) floor($booking->total_amount / 100);
    }

    /**
     * Check if user qualifies for tier upgrade
     */
    public function checkAndUpgradeTier(User $user): void
    {
        $qualifyingTier = LoyaltyTier::where('is_active', true)
            ->where('min_points', '<=', $user->loyalty_points)
            ->orderBy('min_points', 'desc')
            ->first();

        if ($qualifyingTier && $user->loyalty_tier_id !== $qualifyingTier->id) {
            $user->update(['loyalty_tier_id' => $qualifyingTier->id]);
        }
    }

    /**
     * Redeem a reward
     */
    public function redeemReward(User $user, Reward $reward): LoyaltyPoint
    {
        if (!$reward->canBeRedeemedBy($user)) {
            throw new \Exception('You cannot redeem this reward');
        }

        return DB::transaction(function () use ($user, $reward) {
            $newBalance = $user->loyalty_points - $reward->points_cost;
            
            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => -$reward->points_cost,
                'type' => 'redeemed',
                'description' => "Redeemed: {$reward->name}",
                'balance_after' => $newBalance,
            ]);

            $user->update(['loyalty_points' => $newBalance]);
            $reward->increment('times_redeemed');

            return $loyaltyPoint;
        });
    }

    /**
     * Award bonus points (admin/promotional)
     */
    public function awardBonusPoints(User $user, int $points, string $description): LoyaltyPoint
    {
        return DB::transaction(function () use ($user, $points, $description) {
            $newBalance = $user->loyalty_points + $points;
            
            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'bonus',
                'description' => $description,
                'balance_after' => $newBalance,
            ]);

            $user->update(['loyalty_points' => $newBalance]);
            $this->checkAndUpgradeTier($user);

            return $loyaltyPoint;
        });
    }

    /**
     * Expire old points
     */
    public function expirePoints(): void
    {
        $expiredPoints = LoyaltyPoint::where('type', 'earned')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('user', function ($query) {
                $query->whereRaw('loyalty_points <= 0');
            })
            ->get();

        foreach ($expiredPoints as $expired) {
            $user = $expired->user;
            $pointsToDeduct = min($expired->points, $user->loyalty_points);

            if ($pointsToDeduct > 0) {
                $newBalance = $user->loyalty_points - $pointsToDeduct;
                
                LoyaltyPoint::create([
                    'user_id' => $user->id,
                    'points' => -$pointsToDeduct,
                    'type' => 'expired',
                    'description' => 'Points expired',
                    'balance_after' => $newBalance,
                ]);

                $user->update(['loyalty_points' => $newBalance]);
                $this->checkAndUpgradeTier($user);
            }
        }
    }

    /**
     * Get available rewards for user
     */
    public function getAvailableRewards(User $user)
    {
        return Reward::where('is_active', true)
            ->where('points_cost', '<=', $user->loyalty_points)
            ->where(function ($query) use ($user) {
                $query->whereNull('min_tier_id')
                    ->orWhereHas('minTier', function ($q) use ($user) {
                        if ($user->loyaltyTier) {
                            $q->where('min_points', '<=', $user->loyaltyTier->min_points);
                        }
                    });
            })
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_redemptions')
                    ->orWhereRaw('times_redeemed < max_redemptions');
            })
            ->get();
    }

    /**
     * Calculate discount from reward
     */
    public function calculateDiscount(Reward $reward, int $bookingAmount): int
    {
        return match($reward->type) {
            'discount_percentage' => (int) round($bookingAmount * ($reward->value / 10000)), // value is in basis points
            'discount_fixed' => min($reward->value, $bookingAmount),
            default => 0,
        };
    }
}
