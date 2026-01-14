<?php

namespace App\Http\Controllers;

use App\Services\LoyaltyService;
use App\Models\Reward;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user()->load('loyaltyTier');
        
        $pointsHistory = $user->loyaltyPointsHistory()
            ->with('booking')
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($point) {
                return [
                    'id' => $point->id,
                    'points' => $point->points,
                    'type' => $point->type,
                    'description' => $point->description,
                    'balance_after' => $point->balance_after,
                    'created_at' => $point->created_at->format('M d, Y H:i'),
                    'booking_id' => $point->booking_id,
                ];
            });

        $availableRewards = Reward::where('is_active', true)
            ->with(['service', 'minTier'])
            ->get()
            ->map(function ($reward) use ($user) {
                return [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'description' => $reward->description,
                    'points_cost' => $reward->points_cost,
                    'type' => $reward->type,
                    'value' => $reward->value,
                    'service_name' => $reward->service?->name,
                    'min_tier_name' => $reward->minTier?->name,
                    'can_redeem' => $reward->canBeRedeemedBy($user),
                    'is_available' => $reward->isAvailable(),
                    'times_redeemed' => $reward->times_redeemed,
                    'max_redemptions' => $reward->max_redemptions,
                ];
            });

        $allTiers = \App\Models\LoyaltyTier::where('is_active', true)
            ->orderBy('min_points')
            ->get()
            ->map(function ($tier) use ($user) {
                return [
                    'id' => $tier->id,
                    'name' => $tier->name,
                    'min_points' => $tier->min_points,
                    'discount_percentage' => $tier->discount_percentage,
                    'points_multiplier' => $tier->points_multiplier,
                    'color' => $tier->color,
                    'icon' => $tier->icon,
                    'benefits' => $tier->benefits,
                    'is_current' => $user->loyalty_tier_id === $tier->id,
                    'is_unlocked' => $user->loyalty_points >= $tier->min_points,
                ];
            });

        return Inertia::render('Loyalty/Index', [
            'user' => [
                'name' => $user->name,
                'loyalty_points' => $user->loyalty_points,
                'tier' => $user->loyaltyTier ? [
                    'name' => $user->loyaltyTier->name,
                    'icon' => $user->loyaltyTier->icon,
                    'color' => $user->loyaltyTier->color,
                    'discount_percentage' => $user->loyaltyTier->discount_percentage,
                    'points_multiplier' => $user->loyaltyTier->points_multiplier,
                ] : null,
            ],
            'points_history' => $pointsHistory,
            'available_rewards' => $availableRewards,
            'all_tiers' => $allTiers,
        ]);
    }

    public function redeemReward(Request $request, Reward $reward)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards,id',
        ]);

        $user = $request->user();

        try {
            $this->loyaltyService->redeemReward($user, $reward);

            return back()->with('success', 'Reward redeemed successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
