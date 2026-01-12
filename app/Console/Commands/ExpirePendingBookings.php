<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ExpirePendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire pending-payment bookings past their expires_at time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');

        $affected = Booking::query()
            ->where('status', Booking::STATUS_PENDING_PAYMENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->update([
                'status' => Booking::STATUS_EXPIRED,
                'updated_at' => now(),
            ]);

        $this->info("Expired {$affected} booking(s).");

        return self::SUCCESS;
    }
}
