<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServicePrice;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

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

        return response()->json(['data' => $booking]);
    }
}
