<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MyBookingsController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::query()
            ->where('customer_id', $user->id)
            ->with([
                'servicePrice.service:id,name',
                'staff:id,name',
                'payments' => fn ($q) => $q->latest('id'),
            ])
            ->latest('starts_at')
            ->limit(50)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'status' => $booking->status,
                'starts_at' => optional($booking->starts_at)?->toIso8601String(),
                'ends_at' => optional($booking->ends_at)?->toIso8601String(),
                'expires_at' => optional($booking->expires_at)?->toIso8601String(),
                'total_amount' => (int) $booking->total_amount,
                'currency' => (string) $booking->currency,
                'service' => [
                    'id' => $booking->servicePrice?->service?->id,
                    'name' => $booking->servicePrice?->service?->name,
                ],
                'service_price' => [
                    'id' => $booking->servicePrice?->id,
                    'name' => $booking->servicePrice?->name,
                    'duration_min' => (int) ($booking->servicePrice?->duration_min ?? 0),
                ],
                'staff' => [
                    'id' => $booking->staff?->id,
                    'name' => $booking->staff?->name,
                ],
                'payments' => $booking->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'provider' => $p->provider,
                    'provider_ref' => $p->provider_ref,
                    'status' => $p->status,
                    'amount' => (int) $p->amount,
                    'currency' => (string) $p->currency,
                    'paid_at' => optional($p->paid_at)?->toIso8601String(),
                ])->values(),
                'latest_payment' => $booking->payments->first() ? [
                    'id' => $booking->payments->first()->id,
                    'provider' => $booking->payments->first()->provider,
                    'provider_ref' => $booking->payments->first()->provider_ref,
                    'status' => $booking->payments->first()->status,
                    'paid_at' => optional($booking->payments->first()->paid_at)?->toIso8601String(),
                ] : null,
            ])
            ->values();

        return Inertia::render('MyBookings', [
            'bookings' => $bookings,
        ]);
    }
}
