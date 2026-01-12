<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffTimeOff;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingRescheduled;
use App\Notifications\StaffBookingAssigned;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BookingController
{
    private function staffHasScheduleCoveringSlot(Staff $staff, CarbonImmutable $startsAtUtc, CarbonImmutable $endsAtUtc, string $fallbackTimezone): bool
    {
        $staff->loadMissing(['schedules' => fn ($q) => $q->where('active', true)]);

        foreach ($staff->schedules as $schedule) {
            $scheduleTz = $schedule->timezone ?: ($staff->timezone ?: $fallbackTimezone);

            $slotStartLocal = $startsAtUtc->setTimezone($scheduleTz);
            $slotEndLocal = $endsAtUtc->setTimezone($scheduleTz);

            $dow = $slotStartLocal->dayOfWeek;
            if ((int) $schedule->day_of_week !== $dow) {
                continue;
            }

            $scheduleDay = $slotStartLocal->startOfDay();
            $scheduleStart = $scheduleDay->setTimeFromTimeString($schedule->start_time);
            $scheduleEnd = $scheduleDay->setTimeFromTimeString($schedule->end_time);

            if ($scheduleEnd->lessThanOrEqualTo($scheduleStart)) {
                continue;
            }

            if ($slotStartLocal->lt($scheduleStart)) {
                continue;
            }

            if ($slotEndLocal->gt($scheduleEnd)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function staffIsAvailableForSlot(Staff $staff, CarbonImmutable $startsAtUtc, CarbonImmutable $endsAtUtc, ?int $ignoreBookingId, string $fallbackTimezone): bool
    {
        if (! $this->staffHasScheduleCoveringSlot($staff, $startsAtUtc, $endsAtUtc, $fallbackTimezone)) {
            return false;
        }

        $timeOff = StaffTimeOff::query()
            ->where('staff_id', $staff->id)
            ->where('active', true)
            ->where('starts_at', '<', $endsAtUtc)
            ->where('ends_at', '>', $startsAtUtc)
            ->exists();

        if ($timeOff) {
            return false;
        }

        $now = CarbonImmutable::now('UTC');

        $busyQuery = Booking::query()
            ->where('staff_id', $staff->id)
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where(function ($q) use ($now) {
                $q->where('status', Booking::STATUS_CONFIRMED)
                    ->orWhere(function ($q) use ($now) {
                        $q->where('status', Booking::STATUS_PENDING_PAYMENT)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '>', $now);
                    });
            })
            ->where('starts_at', '<', $endsAtUtc)
            ->where('ends_at', '>', $startsAtUtc);

        return ! $busyQuery->exists();
    }

    public function index(Request $request)
    {
        $bookings = Booking::query()
            ->with([
                'customer:id,name,email',
                'staff:id,name',
                'servicePrice.service:id,name',
                'payments' => fn ($q) => $q->latest('id'),
            ])
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'status' => $b->status,
                'starts_at' => optional($b->starts_at)?->toIso8601String(),
                'ends_at' => optional($b->ends_at)?->toIso8601String(),
                'expires_at' => optional($b->expires_at)?->toIso8601String(),
                'total_amount' => (int) $b->total_amount,
                'currency' => (string) $b->currency,
                'customer' => [
                    'id' => $b->customer?->id,
                    'name' => $b->customer?->name,
                    'email' => $b->customer?->email,
                ],
                'service' => [
                    'id' => $b->servicePrice?->service?->id,
                    'name' => $b->servicePrice?->service?->name,
                ],
                'service_price' => [
                    'id' => $b->servicePrice?->id,
                    'name' => $b->servicePrice?->name,
                    'duration_min' => (int) ($b->servicePrice?->duration_min ?? 0),
                ],
                'staff' => [
                    'id' => $b->staff?->id,
                    'name' => $b->staff?->name,
                ],
                'latest_payment' => $b->payments->first() ? [
                    'id' => $b->payments->first()->id,
                    'provider' => $b->payments->first()->provider,
                    'provider_ref' => $b->payments->first()->provider_ref,
                    'status' => $b->payments->first()->status,
                    'paid_at' => optional($b->payments->first()->paid_at)?->toIso8601String(),
                ] : null,
            ])
            ->values();

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Request $request, Booking $booking)
    {
        $booking->load([
            'customer:id,name,email',
            'staff:id,name',
            'servicePrice.service:id,name',
            'servicePrice.service.staff:id,name,timezone,active',
            'payments' => fn ($q) => $q->latest('id'),
            'payments.events' => fn ($q) => $q->latest('id'),
        ]);

        $staffOptions = $booking->servicePrice?->service?->staff
            ?->where('active', true)
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'timezone' => $s->timezone,
            ])
            ->values() ?? collect();

        return Inertia::render('Admin/Bookings/Show', [
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'starts_at' => optional($booking->starts_at)?->toIso8601String(),
                'ends_at' => optional($booking->ends_at)?->toIso8601String(),
                'expires_at' => optional($booking->expires_at)?->toIso8601String(),
                'total_amount' => (int) $booking->total_amount,
                'currency' => (string) $booking->currency,
                'customer' => [
                    'id' => $booking->customer?->id,
                    'name' => $booking->customer?->name,
                    'email' => $booking->customer?->email,
                ],
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
                    'events' => $p->events->map(fn ($e) => [
                        'id' => $e->id,
                        'event_type' => $e->event_type,
                        'received_at' => optional($e->received_at)?->toIso8601String(),
                    ])->values(),
                ])->values(),
            ],
            'staffOptions' => $staffOptions,
        ]);
    }

    public function updateAssignment(Request $request, Booking $booking)
    {
        try {
            $validated = $request->validate([
                'starts_at' => ['required', 'date'],
                'timezone' => ['sometimes', 'string', 'max:64'],
                'staff_id' => ['required', 'integer', 'exists:staff,id'],
            ]);

            $booking->load(['servicePrice.service']);

            $durationMinutes = (int) ($booking->servicePrice?->duration_min ?? 0);
            if ($durationMinutes <= 0) {
                throw ValidationException::withMessages(['starts_at' => 'Booking has invalid duration.']);
            }

            $tz = $validated['timezone'] ?? config('app.timezone');
            $startsAtUtc = CarbonImmutable::parse($validated['starts_at'], $tz)->utc();
            $endsAtUtc = $startsAtUtc->addMinutes($durationMinutes);

            $staff = Staff::query()->whereKey($validated['staff_id'])->where('active', true)->first();
            if (! $staff) {
                throw ValidationException::withMessages(['staff_id' => 'Staff not found or inactive.']);
            }

            $service = $booking->servicePrice?->service;
            if (! $service || ! $service->staff()->whereKey($staff->id)->exists()) {
                throw ValidationException::withMessages(['staff_id' => 'Staff is not assigned to this service.']);
            }

            if (! $this->staffIsAvailableForSlot($staff, $startsAtUtc, $endsAtUtc, $booking->id, $tz)) {
                throw ValidationException::withMessages(['starts_at' => 'Staff is not available for this time.']);
            }

            $oldStartsAt = $booking->starts_at?->setTimezone($tz)->format('l, F j, Y \a\t g:i A');
            $oldStaffId = $booking->staff_id;
            $oldStaffName = $booking->staff?->name;

            $booking->staff_id = $staff->id;
            $booking->starts_at = $startsAtUtc;
            $booking->ends_at = $endsAtUtc;
            $booking->save();

            // Send notifications
            $booking->load('customer');
            $booking->customer?->notify(new BookingRescheduled($booking, $oldStartsAt, $oldStaffName));
            
            // Notify new staff member
            if ($oldStaffId !== $staff->id) {
                $staff->notify(new StaffBookingAssigned($booking));
            }

            return redirect()->route('admin.bookings.show', $booking)
                ->with('success', 'Booking assignment updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('error', 'Failed to update booking assignment: ' . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        $servicePrices = ServicePrice::query()
            ->where('active', true)
            ->with(['service:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn (ServicePrice $p) => [
                'id' => $p->id,
                'service' => [
                    'id' => $p->service?->id,
                    'name' => $p->service?->name,
                ],
                'name' => $p->name,
                'duration_min' => (int) $p->duration_min,
                'amount' => (int) $p->amount,
                'currency' => (string) $p->currency,
            ])
            ->values();

        $staff = Staff::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'timezone']);

        return Inertia::render('Admin/Bookings/Create', [
            'servicePrices' => $servicePrices,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_email' => ['required', 'email'],
            'service_price_id' => ['required', 'integer', 'exists:service_prices,id'],
            'starts_at' => ['required', 'date'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
        ]);

        $customer = User::query()->where('email', $validated['customer_email'])->first();
        if (! $customer) {
            throw ValidationException::withMessages(['customer_email' => 'Customer not found.']);
        }

        $servicePrice = ServicePrice::query()
            ->whereKey($validated['service_price_id'])
            ->where('active', true)
            ->with(['service'])
            ->firstOrFail();

        $durationMinutes = (int) $servicePrice->duration_min;
        if ($durationMinutes <= 0) {
            throw ValidationException::withMessages(['service_price_id' => 'Invalid duration.']);
        }

        $tz = $validated['timezone'] ?? config('app.timezone');
        $startsAtUtc = CarbonImmutable::parse($validated['starts_at'], $tz)->utc();
        $endsAtUtc = $startsAtUtc->addMinutes($durationMinutes);

        $service = $servicePrice->service;
        if (! $service) {
            throw ValidationException::withMessages(['service_price_id' => 'Service not found.']);
        }

        $chosenStaff = null;

        if (! empty($validated['staff_id'])) {
            $staff = Staff::query()->whereKey($validated['staff_id'])->where('active', true)->first();
            if (! $staff) {
                throw ValidationException::withMessages(['staff_id' => 'Staff not found or inactive.']);
            }
            if (! $service->staff()->whereKey($staff->id)->exists()) {
                throw ValidationException::withMessages(['staff_id' => 'Staff is not assigned to this service.']);
            }
            if (! $this->staffIsAvailableForSlot($staff, $startsAtUtc, $endsAtUtc, null, $tz)) {
                throw ValidationException::withMessages(['starts_at' => 'Staff is not available for this time.']);
            }
            $chosenStaff = $staff;
        } else {
            $candidates = Staff::query()
                ->where('active', true)
                ->whereHas('services', fn ($q) => $q->whereKey($service->id))
                ->orderBy('id')
                ->get();

            foreach ($candidates as $staff) {
                if ($this->staffIsAvailableForSlot($staff, $startsAtUtc, $endsAtUtc, null, $tz)) {
                    $chosenStaff = $staff;
                    break;
                }
            }

            if (! $chosenStaff) {
                throw ValidationException::withMessages(['starts_at' => 'No staff available for this time.']);
            }
        }

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'staff_id' => $chosenStaff->id,
            'service_price_id' => $servicePrice->id,
            'status' => Booking::STATUS_CONFIRMED,
            'starts_at' => $startsAtUtc,
            'ends_at' => $endsAtUtc,
            'expires_at' => null,
            'total_amount' => (int) $servicePrice->amount,
            'currency' => (string) $servicePrice->currency,
        ]);

        // Send notifications
        $customer->notify(new BookingConfirmed($booking));
        $chosenStaff->notify(new StaffBookingAssigned($booking));

        return redirect()->route('admin.bookings.show', $booking);
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        try {
            $validated = $request->validate([
                'status' => [
                    'required',
                    'string',
                    Rule::in([
                        Booking::STATUS_CONFIRMED,
                        Booking::STATUS_CANCELLED,
                        Booking::STATUS_REFUNDED,
                        Booking::STATUS_EXPIRED,
                        Booking::STATUS_PENDING_PAYMENT,
                    ]),
                ],
            ]);

            $oldStatus = $booking->status;
            $booking->status = $validated['status'];
            $booking->save();

            // Send notification to customer based on status change
            $booking->load('customer');
            if ($oldStatus !== $validated['status']) {
                if ($validated['status'] === Booking::STATUS_CONFIRMED) {
                    $booking->customer?->notify(new BookingConfirmed($booking));
                } elseif ($validated['status'] === Booking::STATUS_CANCELLED) {
                    $booking->customer?->notify(new BookingCancelled($booking));
                }
            }

            $statusLabel = ucfirst(str_replace('_', ' ', $validated['status']));

            return redirect()->route('admin.bookings.show', $booking)
                ->with('success', "Booking status updated to {$statusLabel}.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('error', 'Failed to update booking status: ' . $e->getMessage());
        }
    }
}
