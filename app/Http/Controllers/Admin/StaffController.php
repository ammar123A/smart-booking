<?php

namespace App\Http\Controllers\Admin;

use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\StaffTimeOff;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StaffController
{
    private function timeToMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return ($h * 60) + $m;
    }

    /**
     * @param array<int, array{day_of_week:int,start_time:string,end_time:string,timezone:string,active?:bool}> $items
     */
    private function assertSchedulesValid(array $items): void
    {
        $byDay = [];

        foreach ($items as $idx => $item) {
            $startMin = $this->timeToMinutes($item['start_time']);
            $endMin = $this->timeToMinutes($item['end_time']);

            if ($endMin <= $startMin) {
                throw ValidationException::withMessages([
                    "schedules.$idx.end_time" => 'End time must be after start time.',
                ]);
            }

            $active = (bool) ($item['active'] ?? true);
            if (! $active) {
                continue;
            }

            $day = (int) $item['day_of_week'];
            $byDay[$day] ??= [];
            $byDay[$day][] = [
                'start' => $startMin,
                'end' => $endMin,
                'index' => $idx,
            ];
        }

        foreach ($byDay as $day => $ranges) {
            usort($ranges, fn ($a, $b) => $a['start'] <=> $b['start']);

            $prev = null;
            foreach ($ranges as $r) {
                if ($prev && $r['start'] < $prev['end']) {
                    throw ValidationException::withMessages([
                        'schedules' => "Schedules overlap on day_of_week {$day}.",
                    ]);
                }
                $prev = $r;
            }
        }
    }

    public function index(Request $request)
    {
        $staff = Staff::query()
            ->withCount(['schedules', 'services'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
        ]);

        Staff::query()->create([
            'name' => $validated['name'],
            'timezone' => $validated['timezone'],
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.staff.index');
    }

    public function edit(Staff $staff)
    {
        $staff->load(['schedules' => function ($q) {
            $q->orderBy('day_of_week')->orderBy('start_time');
        }, 'timeOffs' => function ($q) {
            $q->orderBy('starts_at');
        }, 'services' => function ($q) {
            $q->orderBy('name');
        }]);

        return Inertia::render('Admin/Staff/Edit', [
            'staffMember' => $staff,
        ]);
    }

    public function update(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $staff->update([
            'name' => $validated['name'],
            'timezone' => $validated['timezone'],
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return redirect()->route('admin.staff.edit', $staff);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();

        return redirect()->route('admin.staff.index');
    }

    public function storeSchedule(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['required', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $staff->schedules()->create([
            'day_of_week' => (int) $validated['day_of_week'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'timezone' => $validated['timezone'],
            'active' => (bool) ($validated['active'] ?? true),
        ]);

        return redirect()->route('admin.staff.edit', $staff);
    }

    public function syncSchedules(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'schedules' => ['required', 'array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i'],
            'schedules.*.timezone' => ['required', 'string', 'max:64'],
            'schedules.*.active' => ['sometimes', 'boolean'],
        ]);

        /** @var array<int, array{day_of_week:int,start_time:string,end_time:string,timezone:string,active?:bool}> $items */
        $items = $validated['schedules'];

        $this->assertSchedulesValid($items);

        DB::transaction(function () use ($staff, $items) {
            $staff->schedules()->delete();

            foreach ($items as $item) {
                $staff->schedules()->create([
                    'day_of_week' => (int) $item['day_of_week'],
                    'start_time' => $item['start_time'].':00',
                    'end_time' => $item['end_time'].':00',
                    'timezone' => $item['timezone'],
                    'active' => (bool) ($item['active'] ?? true),
                ]);
            }
        });

        return redirect()->route('admin.staff.edit', $staff);
    }

    public function updateSchedule(Request $request, StaffSchedule $schedule)
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['required', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $schedule->update([
            'day_of_week' => (int) $validated['day_of_week'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'timezone' => $validated['timezone'],
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return redirect()->route('admin.staff.edit', $schedule->staff_id);
    }

    public function destroySchedule(StaffSchedule $schedule)
    {
        $staffId = $schedule->staff_id;
        $schedule->delete();

        return redirect()->route('admin.staff.edit', $staffId);
    }

    public function storeTimeOff(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $tz = $validated['timezone'] ?? ($staff->timezone ?: config('app.timezone'));

        $startsAtUtc = CarbonImmutable::parse($validated['starts_at'], $tz)->utc();
        $endsAtUtc = CarbonImmutable::parse($validated['ends_at'], $tz)->utc();

        if ($endsAtUtc->lessThanOrEqualTo($startsAtUtc)) {
            throw ValidationException::withMessages(['ends_at' => 'End must be after start.']);
        }

        $active = (bool) ($validated['active'] ?? true);

        if ($active) {
            $overlap = StaffTimeOff::query()
                ->where('staff_id', $staff->id)
                ->where('active', true)
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Time off overlaps an existing entry.',
                ]);
            }
        }

        $staff->timeOffs()->create([
            'starts_at' => $startsAtUtc,
            'ends_at' => $endsAtUtc,
            'reason' => $validated['reason'] ?? null,
            'active' => $active,
        ]);

        return redirect()->route('admin.staff.edit', $staff);
    }

    public function destroyTimeOff(StaffTimeOff $timeOff)
    {
        $staffId = $timeOff->staff_id;
        $timeOff->delete();

        return redirect()->route('admin.staff.edit', $staffId);
    }
}
