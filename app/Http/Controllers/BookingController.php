<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServicePrice;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function store(Request $request, BookingService $bookingService)
    {
        $validated = $request->validate([
            'service_price_id' => ['required', 'integer', 'exists:service_prices,id'],
            'starts_at' => ['required', 'date'],
            'timezone' => ['sometimes', 'string'],
        ]);

        /** @var ServicePrice $servicePrice */
        $servicePrice = ServicePrice::query()
            ->whereKey($validated['service_price_id'])
            ->where('active', true)
            ->firstOrFail();

        $startsAtUtc = CarbonImmutable::parse($validated['starts_at'])->utc();
        $timezone = $validated['timezone'] ?? null;

        $booking = $bookingService->createPendingBookingAutoAssign($request->user(), $servicePrice, $startsAtUtc, $timezone);

        return response()->json(['data' => $booking], 201);
    }

    public function show(Request $request, Booking $booking)
    {
        $user = $request->user();
        if ($booking->customer_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403);
        }

        $booking->load([
            'servicePrice.service:id,name',
            'staff:id,name',
            'payments' => fn ($q) => $q->latest('id'),
            'review:id,booking_id,rating,comment',
        ]);

        $data = [
            'id'           => $booking->id,
            'status'       => $booking->status,
            'starts_at'    => optional($booking->starts_at)?->toIso8601String(),
            'ends_at'      => optional($booking->ends_at)?->toIso8601String(),
            'expires_at'   => optional($booking->expires_at)?->toIso8601String(),
            'total_amount' => (int) $booking->total_amount,
            'currency'     => (string) $booking->currency,
            'service' => [
                'id'   => $booking->servicePrice?->service?->id,
                'name' => $booking->servicePrice?->service?->name,
            ],
            'service_price' => [
                'id'           => $booking->servicePrice?->id,
                'name'         => $booking->servicePrice?->name,
                'duration_min' => (int) ($booking->servicePrice?->duration_min ?? 0),
            ],
            'staff' => [
                'id'   => $booking->staff?->id,
                'name' => $booking->staff?->name,
            ],
            'payments' => $booking->payments->map(fn ($p) => [
                'id'           => $p->id,
                'provider'     => $p->provider,
                'provider_ref' => $p->provider_ref,
                'status'       => $p->status,
                'amount'       => (int) $p->amount,
                'currency'     => (string) $p->currency,
                'paid_at'      => optional($p->paid_at)?->toIso8601String(),
            ])->values(),
            'latest_payment' => $booking->payments->first() ? [
                'id'           => $booking->payments->first()->id,
                'provider'     => $booking->payments->first()->provider,
                'provider_ref' => $booking->payments->first()->provider_ref,
                'status'       => $booking->payments->first()->status,
                'paid_at'      => optional($booking->payments->first()->paid_at)?->toIso8601String(),
            ] : null,
            'review' => $booking->review ? [
                'id'      => $booking->review->id,
                'rating'  => $booking->review->rating,
                'comment' => $booking->review->comment,
            ] : null,
            'can_review' => $booking->status === Booking::STATUS_CONFIRMED
                && $booking->ends_at?->isPast()
                && ! $booking->review,
        ];

        return Inertia::render('Booking/Show', ['booking' => $data]);
    }
}
