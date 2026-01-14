<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Services\LoyaltyService;

class AwardLoyaltyPoints
{
    public function __construct(
        protected LoyaltyService $loyaltyService
    ) {}

    public function handle(BookingCompleted $event): void
    {
        $this->loyaltyService->awardPoints(
            $event->booking->customer,
            $event->booking
        );
    }
}
