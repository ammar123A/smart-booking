<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServicePrice;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ServiceAvailabilityController
{
    public function __construct(private readonly AvailabilityService $availability)
    {
    }

    public function __invoke(Request $request, Service $service)
    {
        $validated = $request->validate([
            'service_price_id' => ['required', 'integer', 'exists:service_prices,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'timezone' => ['sometimes', 'string'],
        ]);

        /** @var ServicePrice $servicePrice */
        $servicePrice = ServicePrice::query()->whereKey($validated['service_price_id'])->firstOrFail();

        if ($servicePrice->service_id !== $service->id) {
            abort(404);
        }

        $timezone = $validated['timezone'] ?? config('app.timezone');
        $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['date'], $timezone);

        $slots = $this->availability->dailySlots($service, $servicePrice, $date, $timezone);

        return response()->json([
            'service_id' => $service->id,
            'service_price_id' => $servicePrice->id,
            'date' => $validated['date'],
            'timezone' => $timezone,
            'duration_min' => (int) $servicePrice->duration_min,
            'slots' => $slots,
        ]);
    }
}
