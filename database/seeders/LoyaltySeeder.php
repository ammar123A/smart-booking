<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LoyaltyTier;
use App\Models\Reward;

class LoyaltySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Loyalty Tiers
        $bronze = LoyaltyTier::create([
            'name' => 'Bronze',
            'slug' => 'bronze',
            'min_points' => 0,
            'discount_percentage' => 0,
            'points_multiplier' => 1.0,
            'color' => '#CD7F32',
            'icon' => '🥉',
            'benefits' => ['Earn 1 point per RM1 spent', 'Access to basic rewards'],
            'is_active' => true,
        ]);

        $silver = LoyaltyTier::create([
            'name' => 'Silver',
            'slug' => 'silver',
            'min_points' => 500,
            'discount_percentage' => 5.0,
            'points_multiplier' => 1.25,
            'color' => '#C0C0C0',
            'icon' => '🥈',
            'benefits' => [
                'Earn 1.25x points',
                '5% discount on all bookings',
                'Priority booking',
                'Birthday bonus points'
            ],
            'is_active' => true,
        ]);

        $gold = LoyaltyTier::create([
            'name' => 'Gold',
            'slug' => 'gold',
            'min_points' => 1500,
            'discount_percentage' => 10.0,
            'points_multiplier' => 1.5,
            'color' => '#FFD700',
            'icon' => '🥇',
            'benefits' => [
                'Earn 1.5x points',
                '10% discount on all bookings',
                'Free service upgrade once per month',
                'Priority customer support',
                'Early access to new services'
            ],
            'is_active' => true,
        ]);

        $platinum = LoyaltyTier::create([
            'name' => 'Platinum',
            'slug' => 'platinum',
            'min_points' => 3000,
            'discount_percentage' => 15.0,
            'points_multiplier' => 2.0,
            'color' => '#E5E4E2',
            'icon' => '💎',
            'benefits' => [
                'Earn 2x points',
                '15% discount on all bookings',
                'Free service upgrade on every booking',
                'Dedicated account manager',
                'Exclusive platinum-only services',
                'Quarterly bonus points'
            ],
            'is_active' => true,
        ]);

        // Create Rewards
        Reward::create([
            'name' => '5% Discount Voucher',
            'description' => 'Get 5% off your next booking',
            'points_cost' => 100,
            'type' => 'discount_percentage',
            'value' => 500, // 5.00% stored as basis points
            'min_tier_id' => null,
            'is_active' => true,
        ]);

        Reward::create([
            'name' => '10% Discount Voucher',
            'description' => 'Get 10% off your next booking',
            'points_cost' => 200,
            'type' => 'discount_percentage',
            'value' => 1000, // 10.00%
            'min_tier_id' => $silver->id,
            'is_active' => true,
        ]);

        Reward::create([
            'name' => 'RM 20 Off',
            'description' => 'Get RM 20 off your next booking',
            'points_cost' => 250,
            'type' => 'discount_fixed',
            'value' => 2000, // RM 20 in cents
            'min_tier_id' => null,
            'is_active' => true,
        ]);

        Reward::create([
            'name' => 'RM 50 Off',
            'description' => 'Get RM 50 off your next booking',
            'points_cost' => 500,
            'type' => 'discount_fixed',
            'value' => 5000, // RM 50 in cents
            'min_tier_id' => $gold->id,
            'is_active' => true,
        ]);

        Reward::create([
            'name' => '20% Discount Voucher',
            'description' => 'Get 20% off your next booking (Platinum members only)',
            'points_cost' => 400,
            'type' => 'discount_percentage',
            'value' => 2000, // 20.00%
            'min_tier_id' => $platinum->id,
            'is_active' => true,
        ]);

        Reward::create([
            'name' => 'RM 100 Off',
            'description' => 'Get RM 100 off your next booking (Limited availability)',
            'points_cost' => 800,
            'type' => 'discount_fixed',
            'value' => 10000, // RM 100 in cents
            'min_tier_id' => $platinum->id,
            'max_redemptions' => 50,
            'is_active' => true,
        ]);
    }
}

