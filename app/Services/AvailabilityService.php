<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffTimeOff;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class AvailabilityService
{
    /**
     * @return array<int, array{start_at:string,end_at:string,available_staff_count:int}>
     */
    public function dailySlots(Service $service, ServicePrice $servicePrice, CarbonImmutable $date, string $timezone, int $slotStepMinutes = 15): array
    {
        $dayOfWeek = $date->setTimezone($timezone)->dayOfWeek; // 0=Sun
        $durationMinutes = (int) $servicePrice->duration_min;

        $staffMembers = Staff::query()
            ->where('active', true)
            ->whereHas('services', fn ($q) => $q->whereKey($service->id))
            ->with(['schedules' => fn ($q) => $q->where('active', true)->where('day_of_week', $dayOfWeek)])
            ->get()
            ->filter(fn (Staff $staff) => $staff->schedules->isNotEmpty())
            ->values();

        if ($staffMembers->isEmpty()) {
            return [];
        }

        $localDayStart = $date->setTimezone($timezone)->startOfDay();
        $localDayEnd = $localDayStart->addDay();
        $dayStartUtc = $localDayStart->utc();
        $dayEndUtc = $localDayEnd->utc();

        $now = CarbonImmutable::now('UTC');

        $bookingsByStaff = Booking::query()
            ->whereIn('staff_id', $staffMembers->pluck('id')->all())
            ->where(function ($q) use ($now) {
                $q->where('status', Booking::STATUS_CONFIRMED)
                    ->orWhere(function ($q) use ($now) {
                        $q->where('status', Booking::STATUS_PENDING_PAYMENT)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '>', $now);
                    });
            })
            ->where('starts_at', '<', $dayEndUtc)
            ->where('ends_at', '>', $dayStartUtc)
            ->get()
            ->groupBy('staff_id');

        $timeOffsByStaff = StaffTimeOff::query()
            ->whereIn('staff_id', $staffMembers->pluck('id')->all())
            ->where('active', true)
            ->where('starts_at', '<', $dayEndUtc)
            ->where('ends_at', '>', $dayStartUtc)
            ->get()
            ->groupBy('staff_id');

        $slotToStaffIds = [];

        foreach ($staffMembers as $staff) {
            $staffBookings = $bookingsByStaff->get($staff->id, collect());
            $staffTimeOffs = $timeOffsByStaff->get($staff->id, collect());

            foreach ($staff->schedules as $schedule) {
                $scheduleTz = $schedule->timezone ?: ($staff->timezone ?: $timezone);

                $scheduleDay = $date->setTimezone($scheduleTz)->startOfDay();

                $scheduleStart = $scheduleDay->setTimeFromTimeString($schedule->start_time);
                $scheduleEnd = $scheduleDay->setTimeFromTimeString($schedule->end_time);

                if ($scheduleEnd->lessThanOrEqualTo($scheduleStart)) {
                    continue;
                }

                $lastStart = $scheduleEnd->subMinutes($durationMinutes);

                for ($slotStartLocal = $scheduleStart; $slotStartLocal->lessThanOrEqualTo($lastStart); $slotStartLocal = $slotStartLocal->addMinutes($slotStepMinutes)) {
                    $slotStartUtc = $slotStartLocal->utc();
                    $slotEndUtc = $slotStartUtc->addMinutes($durationMinutes);

                    $overlaps = $staffBookings->first(function (Booking $booking) use ($slotStartUtc, $slotEndUtc) {
                        return $booking->starts_at->lt($slotEndUtc) && $booking->ends_at->gt($slotStartUtc);
                    });

                    if ($overlaps) {
                        continue;
                    }

                    $timeOff = $staffTimeOffs->first(function ($t) use ($slotStartUtc, $slotEndUtc) {
                        return $t->starts_at->lt($slotEndUtc) && $t->ends_at->gt($slotStartUtc);
                    });

                    if ($timeOff) {
                        continue;
                    }

                    $key = $slotStartUtc->toIso8601String();
                    $slotToStaffIds[$key] ??= [];
                    $slotToStaffIds[$key][$staff->id] = true;
                }
            }
        }

        $slots = [];
        foreach ($slotToStaffIds as $startAtIso => $staffIdsMap) {
            $startAt = CarbonImmutable::parse($startAtIso, 'UTC');
            $endAt = $startAt->addMinutes($durationMinutes);

            $slots[] = [
                'start_at' => $startAt->toIso8601String(),
                'end_at' => $endAt->toIso8601String(),
                'available_staff_count' => count($staffIdsMap),
            ];
        }

        usort($slots, fn ($a, $b) => strcmp(Arr::get($a, 'start_at', ''), Arr::get($b, 'start_at', '')));

        return $slots;
    }
}
