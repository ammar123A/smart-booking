<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_confirmed_booking_with_auto_assign(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

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

        $date = CarbonImmutable::parse('2026-01-05T09:00:00Z'); // Monday
        $dow = $date->dayOfWeek;

        StaffSchedule::create([
            'staff_id' => $staff->id,
            'day_of_week' => $dow,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'timezone' => 'UTC',
            'active' => true,
        ]);

        $res = $this->actingAs($admin)
            ->post(route('admin.bookings.store'), [
                'customer_email' => 'customer@example.com',
                'service_price_id' => $price->id,
                'starts_at' => $date->toIso8601String(),
                'timezone' => 'UTC',
                'staff_id' => null,
            ]);

        $booking = Booking::query()->latest('id')->first();
        $this->assertNotNull($booking);

        $res->assertRedirect(route('admin.bookings.show', $booking));
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertSame($staff->id, $booking->staff_id);
        $this->assertSame($price->id, $booking->service_price_id);
    }

    public function test_admin_can_reschedule_and_reassign_booking(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();

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

        $date = CarbonImmutable::parse('2026-01-05T09:00:00Z'); // Monday
        $dow = $date->dayOfWeek;

        StaffSchedule::create([
            'staff_id' => $staff1->id,
            'day_of_week' => $dow,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'timezone' => 'UTC',
            'active' => true,
        ]);

        StaffSchedule::create([
            'staff_id' => $staff2->id,
            'day_of_week' => $dow,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'timezone' => 'UTC',
            'active' => true,
        ]);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'staff_id' => $staff1->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_CONFIRMED,
            'starts_at' => $date,
            'ends_at' => $date->addMinutes(30),
            'expires_at' => null,
            'total_amount' => 5000,
            'currency' => 'MYR',
        ]);

        $newStart = CarbonImmutable::parse('2026-01-05T10:00:00Z');

        $res = $this->actingAs($admin)
            ->patch(route('admin.bookings.assignment', $booking), [
                'staff_id' => $staff2->id,
                'starts_at' => $newStart->toIso8601String(),
                'timezone' => 'UTC',
            ]);

        $res->assertRedirect(route('admin.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame($staff2->id, $booking->staff_id);
        $this->assertTrue($booking->starts_at->equalTo($newStart));
        $this->assertTrue($booking->ends_at->equalTo($newStart->addMinutes(30)));
    }
}
