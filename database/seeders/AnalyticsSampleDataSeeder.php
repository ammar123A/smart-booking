<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AnalyticsSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::query()->where('email', 'test@example.com')->first();
        
        if (!$customer) {
            return;
        }

        $servicePrices = ServicePrice::query()->where('active', true)->get();
        $staff = Staff::query()->where('active', true)->get();

        if ($servicePrices->isEmpty() || $staff->isEmpty()) {
            return;
        }

        $now = CarbonImmutable::now();
        
        // Track used time slots per staff per day
        $usedSlots = [];
        
        // Create bookings for the past 30 days
        for ($i = 30; $i >= 0; $i--) {
            $date = $now->subDays($i);
            
            // Create 2-5 bookings per day (more on weekdays)
            $bookingsPerDay = $date->isWeekday() ? rand(3, 5) : rand(1, 3);
            
            for ($j = 0; $j < $bookingsPerDay; $j++) {
                $servicePrice = $servicePrices->random();
                $selectedStaff = $staff->random();
                
                // Find an available time slot
                $maxAttempts = 20;
                $attempt = 0;
                $slotFound = false;
                
                while (!$slotFound && $attempt < $maxAttempts) {
                    // Random time between 9 AM and 5 PM
                    $hour = rand(9, 16);
                    $startsAt = $date->setTime($hour, 0, 0);
                    $endsAt = $startsAt->addMinutes((int) $servicePrice->duration_min);
                    
                    $slotKey = $selectedStaff->id . '_' . $date->toDateString() . '_' . $hour;
                    
                    if (!isset($usedSlots[$slotKey])) {
                        $usedSlots[$slotKey] = true;
                        $slotFound = true;
                    } else {
                        $selectedStaff = $staff->random();
                        $attempt++;
                    }
                }
                
                if (!$slotFound) {
                    continue; // Skip if no slot found
                }
                
                // 80% confirmed, 10% cancelled, 5% pending, 5% refunded
                $statusRand = rand(1, 100);
                if ($statusRand <= 80) {
                    $status = Booking::STATUS_CONFIRMED;
                } elseif ($statusRand <= 90) {
                    $status = Booking::STATUS_CANCELLED;
                } elseif ($statusRand <= 95) {
                    $status = Booking::STATUS_PENDING_PAYMENT;
                } else {
                    $status = Booking::STATUS_REFUNDED;
                }
                
                $booking = Booking::create([
                    'customer_id' => $customer->id,
                    'staff_id' => $selectedStaff->id,
                    'service_price_id' => $servicePrice->id,
                    'status' => $status,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'expires_at' => $status === Booking::STATUS_PENDING_PAYMENT ? $startsAt->subMinutes(30) : null,
                    'total_amount' => (int) $servicePrice->amount,
                    'currency' => (string) $servicePrice->currency,
                    'created_at' => $startsAt->subHours(rand(24, 168)), // Created 1-7 days before
                ]);

                // Create payment for confirmed bookings
                if (in_array($status, [Booking::STATUS_CONFIRMED, Booking::STATUS_REFUNDED])) {
                    Payment::create([
                        'booking_id' => $booking->id,
                        'provider' => 'stripe',
                        'provider_ref' => 'test_' . uniqid(),
                        'status' => Payment::STATUS_PAID,
                        'amount' => $booking->total_amount,
                        'currency' => $booking->currency,
                        'paid_at' => $startsAt->subHours(rand(1, 24)),
                    ]);
                }
            }
        }

        $this->command->info('Created sample analytics data for the past 30 days.');
    }
}
