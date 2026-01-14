<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find a confirmed booking for user 1
$booking = App\Models\Booking::where('status', 'confirmed')
    ->where('customer_id', 1)
    ->first();

if ($booking) {
    // Trigger the event to award points
    event(new App\Events\BookingCompleted($booking));
    
    // Refresh user to see new points
    $user = $booking->customer->fresh();
    
    echo "✓ Points awarded for booking #{$booking->id}\n";
    echo "  Customer: {$user->name}\n";
    echo "  Loyalty Points: {$user->loyalty_points}\n";
    echo "  Tier: " . ($user->loyaltyTier ? $user->loyaltyTier->name : 'None') . "\n";
} else {
    echo "✗ No confirmed booking found for user 1\n";
    echo "  Creating a test completed booking...\n";
    
    // Create a test booking
    $user = App\Models\User::find(1);
    $service = App\Models\Service::first();
    $staff = App\Models\Staff::first();
    
    if ($user && $service && $staff) {
        $booking = App\Models\Booking::create([
            'customer_id' => $user->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->subDays(7)->addMinutes(60),
            'total_amount' => 10000, // RM 100
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        
        event(new App\Events\BookingCompleted($booking));
        
        $user = $user->fresh();
        
        echo "✓ Test booking created and points awarded!\n";
        echo "  Booking ID: #{$booking->id}\n";
        echo "  Amount: RM " . ($booking->total_amount / 100) . "\n";
        echo "  Points Earned: 100\n";
        echo "  Total Points: {$user->loyalty_points}\n";
        echo "  Tier: " . ($user->loyaltyTier ? $user->loyaltyTier->name : 'None') . "\n";
    }
}
