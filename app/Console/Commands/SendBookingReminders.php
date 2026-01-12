<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\BookingReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send booking reminder notifications 24 hours before appointment';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        
        // Find bookings that are confirmed and start between 23.5 and 24.5 hours from now
        $reminderWindowStart = $now->addHours(23)->addMinutes(30);
        $reminderWindowEnd = $now->addHours(24)->addMinutes(30);

        $bookings = Booking::query()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereBetween('starts_at', [$reminderWindowStart, $reminderWindowEnd])
            ->with(['customer', 'staff', 'servicePrice.service'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings found that need reminders.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($bookings as $booking) {
            if ($booking->customer) {
                $booking->customer->notify(new BookingReminder($booking));
                $count++;
            }
        }

        $this->info("Sent {$count} booking reminder(s).");
        
        return Command::SUCCESS;
    }
}
