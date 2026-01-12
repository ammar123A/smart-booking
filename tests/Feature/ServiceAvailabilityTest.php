<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\StaffTimeOff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_slots_with_available_staff_count(): void
    {
        $user = User::factory()->create();

        $service = Service::create(['name' => 'Test Service', 'active' => true]);
        $price = ServicePrice::create([
            'service_id' => $service->id,
            'name' => '30 minutes',
            'duration_min' => 30,
            'amount' => 5000,
            'currency' => 'MYR',
            'active' => true,
        ]);

        $staff1 = Staff::create(['name' => 'S1', 'timezone' => 'UTC', 'active' => true]);
        $staff2 = Staff::create(['name' => 'S2', 'timezone' => 'UTC', 'active' => true]);

        $service->staff()->attach([$staff1->id, $staff2->id]);

        // Monday
        $date = CarbonImmutable::parse('2026-01-05', 'UTC');
        $dow = $date->dayOfWeek;

        foreach ([$staff1, $staff2] as $staff) {
            StaffSchedule::create([
                'staff_id' => $staff->id,
                'day_of_week' => $dow,
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'timezone' => 'UTC',
                'active' => true,
            ]);
        }

        // Block staff1 for the 09:00-09:30 slot.
        Booking::create([
            'customer_id' => $user->id,
            'staff_id' => $staff1->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_CONFIRMED,
            'starts_at' => $date->setTime(9, 0)->utc(),
            'ends_at' => $date->setTime(9, 30)->utc(),
            'expires_at' => null,
            'total_amount' => 5000,
            'currency' => 'MYR',
        ]);

        // Block staff2 for the 09:00-09:30 slot via time off.
        StaffTimeOff::create([
            'staff_id' => $staff2->id,
            'starts_at' => $date->setTime(9, 0)->utc(),
            'ends_at' => $date->setTime(9, 30)->utc(),
            'reason' => 'Break',
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('services.availability', $service).'?service_price_id='.$price->id.'&date='.$date->format('Y-m-d').'&timezone=UTC');

        $response->assertOk();
        $response->assertJsonStructure([
            'service_id',
            'service_price_id',
            'date',
            'timezone',
            'duration_min',
            'slots' => [
                '*' => ['start_at', 'end_at', 'available_staff_count'],
            ],
        ]);

        $slots = $response->json('slots');
        $this->assertNotEmpty($slots);

        $slotStart0900 = $date->setTime(9, 0)->utc()->toIso8601String();
        $slotStart0930 = $date->setTime(9, 30)->utc()->toIso8601String();

        $starts = array_map(fn ($s) => $s['start_at'], $slots);
        $this->assertFalse(in_array($slotStart0900, $starts, true));

        $slot0930 = collect($slots)->firstWhere('start_at', $slotStart0930);
        $this->assertNotNull($slot0930);
        $this->assertSame(2, $slot0930['available_staff_count']);
    }
}
