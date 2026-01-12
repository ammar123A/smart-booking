<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\StaffTimeOff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function createPendingBookingAutoAssign(User $customer, ServicePrice $servicePrice, CarbonImmutable $startsAtUtc, ?string $fallbackTimezone = null): Booking
    {
        /** @var Service $service */
        $service = $servicePrice->service()->firstOrFail();

        $durationMinutes = (int) $servicePrice->duration_min;
        $endsAtUtc = $startsAtUtc->addMinutes($durationMinutes);

        if ($endsAtUtc->lessThanOrEqualTo($startsAtUtc)) {
            throw ValidationException::withMessages(['starts_at' => 'Invalid booking duration.']);
        }

        $now = CarbonImmutable::now('UTC');
        if ($startsAtUtc->lessThan($now)) {
            throw ValidationException::withMessages(['starts_at' => 'Start time must be in the future.']);
        }

        $fallbackTimezone = $fallbackTimezone ?: config('app.timezone');

        $staffMembers = Staff::query()
            ->where('active', true)
            ->whereHas('services', fn ($q) => $q->whereKey($service->id))
            ->with(['schedules' => fn ($q) => $q->where('active', true)])
            ->orderBy('id')
            ->get();

        if ($staffMembers->isEmpty()) {
            throw ValidationException::withMessages(['starts_at' => 'No staff available for this service.']);
        }

        $candidateStaff = $staffMembers
            ->filter(fn (Staff $staff) => $this->staffHasScheduleCoveringSlot($staff, $startsAtUtc, $endsAtUtc, $fallbackTimezone))
            ->values();

        if ($candidateStaff->isEmpty()) {
            throw ValidationException::withMessages(['starts_at' => 'No staff scheduled for this time.']);
        }

        $timeOffsByStaff = StaffTimeOff::query()
            ->whereIn('staff_id', $candidateStaff->pluck('id')->all())
            ->where('active', true)
            ->where('starts_at', '<', $endsAtUtc)
            ->where('ends_at', '>', $startsAtUtc)
            ->get()
            ->groupBy('staff_id');

        $candidateStaff = $candidateStaff
            ->filter(fn (Staff $staff) => $timeOffsByStaff->get($staff->id, collect())->isEmpty())
            ->values();

        if ($candidateStaff->isEmpty()) {
            throw ValidationException::withMessages(['starts_at' => 'No staff available for this time.']);
        }

        $expiresAtUtc = $now->addMinutes(30);

        /** @var Booking|null $created */
        $created = DB::transaction(function () use ($candidateStaff, $customer, $servicePrice, $startsAtUtc, $endsAtUtc, $expiresAtUtc, $now) {
            foreach ($candidateStaff as $staff) {
                $busy = Booking::query()
                    ->where('staff_id', $staff->id)
                    ->where(function ($q) use ($now) {
                        $q->where('status', Booking::STATUS_CONFIRMED)
                            ->orWhere(function ($q) use ($now) {
                                $q->where('status', Booking::STATUS_PENDING_PAYMENT)
                                    ->whereNotNull('expires_at')
                                    ->where('expires_at', '>', $now);
                            });
                    })
                    ->where('starts_at', '<', $endsAtUtc)
                    ->where('ends_at', '>', $startsAtUtc)
                    ->exists();

                if ($busy) {
                    continue;
                }

                try {
                    return Booking::create([
                        'customer_id' => $customer->id,
                        'staff_id' => $staff->id,
                        'service_price_id' => $servicePrice->id,
                        'status' => Booking::STATUS_PENDING_PAYMENT,
                        'starts_at' => $startsAtUtc,
                        'ends_at' => $endsAtUtc,
                        'expires_at' => $expiresAtUtc,
                        'total_amount' => (int) $servicePrice->amount,
                        'currency' => (string) $servicePrice->currency,
                    ]);
                } catch (QueryException $e) {
                    // PostgreSQL exclusion constraint violation for overlaps is SQLSTATE 23P01.
                    $sqlState = $e->errorInfo[0] ?? null;
                    if ($sqlState === '23P01') {
                        continue;
                    }

                    throw $e;
                }
            }

            return null;
        });

        if (! $created) {
            throw ValidationException::withMessages(['starts_at' => 'Slot is no longer available.']);
        }

        return $created;
    }

    private function staffHasScheduleCoveringSlot(Staff $staff, CarbonImmutable $startsAtUtc, CarbonImmutable $endsAtUtc, string $fallbackTimezone): bool
    {
        foreach ($staff->schedules as $schedule) {
            if (! $schedule instanceof StaffSchedule) {
                continue;
            }

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
}
