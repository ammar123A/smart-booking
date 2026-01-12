<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeBookingPayment implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $paymentId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()->with('booking')->whereKey($this->paymentId)->first();
        if (! $payment) {
            throw new ModelNotFoundException();
        }

        if ($payment->status !== Payment::STATUS_PAID) {
            return;
        }

        $now = CarbonImmutable::now('UTC');

        DB::transaction(function () use ($payment, $now) {
            /** @var Booking $booking */
            $booking = Booking::query()->whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
                return;
            }

            if ($booking->expires_at && $booking->expires_at->lte($now)) {
                $payment->meta = array_merge((array) $payment->meta, ['late_paid' => true]);
                $payment->save();
                return;
            }

            $booking->status = Booking::STATUS_CONFIRMED;
            $booking->save();
        });
    }
}
