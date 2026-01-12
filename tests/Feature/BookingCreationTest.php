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

class BookingCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_assigns_an_available_staff_member(): void
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

        // Monday schedule 09:00-10:00
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

        // Block staff1 from 09:00-09:30
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

        $response = $this->actingAs($user)->postJson(route('bookings.store'), [
            'service_price_id' => $price->id,
            'starts_at' => $date->setTime(9, 0)->toIso8601String(),
            'timezone' => 'UTC',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', Booking::STATUS_PENDING_PAYMENT);
        $this->assertSame($staff2->id, $response->json('data.staff_id'));
    }

    public function test_rejects_booking_when_only_available_staff_is_on_time_off(): void
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

        // staff1 is booked; staff2 is on time off.
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

        StaffTimeOff::create([
            'staff_id' => $staff2->id,
            'starts_at' => $date->setTime(9, 0)->utc(),
            'ends_at' => $date->setTime(9, 30)->utc(),
            'reason' => 'Vacation',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('bookings.store'), [
            'service_price_id' => $price->id,
            'starts_at' => $date->setTime(9, 0)->toIso8601String(),
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['starts_at']);
    }

    public function test_expire_command_marks_pending_bookings_expired(): void
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

        $staff = Staff::create(['name' => 'S1', 'timezone' => 'UTC', 'active' => true]);
        $service->staff()->attach([$staff->id]);

        $startsAt = CarbonImmutable::now('UTC')->addHour();
        $booking = Booking::create([
            'customer_id' => $user->id,
            'staff_id' => $staff->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_PENDING_PAYMENT,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(30),
            'expires_at' => CarbonImmutable::now('UTC')->subMinute(),
            'total_amount' => 5000,
            'currency' => 'MYR',
        ]);

        $this->artisan('bookings:expire-pending')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_EXPIRED, $booking->status);
    }
}
