<?php

namespace App\Console\Commands;

use App\Events\BookingCompleted;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AutoCompleteBookings extends Command
{
    protected $signature = 'bookings:auto-complete';

    protected $description = 'Mark confirmed bookings whose end time has passed as completed';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');

        $bookings = Booking::query()
            ->where('status', Booking::STATUS_CONFIRMED)
            ->where('ends_at', '<', $now)
            ->get();

        foreach ($bookings as $booking) {
            $booking->update(['status' => Booking::STATUS_COMPLETED]);
            event(new BookingCompleted($booking));
        }

        $this->info("Auto-completed {$bookings->count()} booking(s).");

        return self::SUCCESS;
    }
}
