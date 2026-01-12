<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SenangPayStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_poll_payment_status_for_own_booking(): void
    {
        Role::findOrCreate('admin');

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

        $startsAt = CarbonImmutable::parse('2026-01-05T09:00:00Z');

        $booking = Booking::create([
            'customer_id' => $user->id,
            'staff_id' => $staff->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_PENDING_PAYMENT,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(30),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(10),
            'total_amount' => 5000,
            'currency' => 'MYR',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => Payment::PROVIDER_SENANGPAY,
            'provider_ref' => 'ORD-123',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
            'currency' => 'MYR',
            'meta' => null,
        ]);

        $res = $this->actingAs($user)->getJson(route('payments.senangpay.status', ['order_id' => $payment->provider_ref]));

        $res->assertOk();
        $res->assertJsonPath('data.order_id', 'ORD-123');
        $res->assertJsonPath('data.payment_status', Payment::STATUS_PENDING);
        $res->assertJsonPath('data.booking_id', $booking->id);
    }

    public function test_customer_cannot_poll_other_users_payment(): void
    {
        Role::findOrCreate('admin');

        $owner = User::factory()->create();
        $other = User::factory()->create();

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

        $startsAt = CarbonImmutable::parse('2026-01-05T09:00:00Z');

        $booking = Booking::create([
            'customer_id' => $owner->id,
            'staff_id' => $staff->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_PENDING_PAYMENT,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(30),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(10),
            'total_amount' => 5000,
            'currency' => 'MYR',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => Payment::PROVIDER_SENANGPAY,
            'provider_ref' => 'ORD-999',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
            'currency' => 'MYR',
            'meta' => null,
        ]);

        $res = $this->actingAs($other)->getJson(route('payments.senangpay.status', ['order_id' => $payment->provider_ref]));
        $res->assertForbidden();
    }
}
