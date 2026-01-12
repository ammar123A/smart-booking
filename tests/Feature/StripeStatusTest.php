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

class StripeStatusTest extends TestCase
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
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_ref' => 'stripe_ref_abc',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
            'currency' => 'MYR',
            'paid_at' => null,
            'meta' => ['session_id' => 'cs_test_abc123'],
        ]);

        $response = $this->actingAs($user)->getJson(route('payments.stripe.status', [
            'session_id' => 'cs_test_abc123',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.payment_id', $payment->id);
        $response->assertJsonPath('data.booking_id', $booking->id);
        $response->assertJsonPath('data.payment_status', 'pending');
    }

    public function test_admin_can_poll_payment_status_for_any_booking(): void
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

        $staff = Staff::create(['name' => 'S1', 'timezone' => 'UTC', 'active' => true]);
        $service->staff()->attach([$staff->id]);

        $startsAt = CarbonImmutable::parse('2026-01-05T09:00:00Z');

        $booking = Booking::create([
            'customer_id' => $customer->id,
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
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_ref' => 'stripe_ref_xyz',
            'status' => Payment::STATUS_PAID,
            'amount' => 5000,
            'currency' => 'MYR',
            'paid_at' => now(),
            'meta' => ['session_id' => 'cs_test_xyz789'],
        ]);

        $response = $this->actingAs($admin)->getJson(route('payments.stripe.status', [
            'session_id' => 'cs_test_xyz789',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.payment_status', 'paid');
    }

    public function test_customer_cannot_poll_another_customers_payment(): void
    {
        Role::findOrCreate('admin');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

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
            'customer_id' => $user1->id,
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
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_ref' => 'stripe_ref_forbidden',
            'status' => Payment::STATUS_PENDING,
            'amount' => 5000,
            'currency' => 'MYR',
            'paid_at' => null,
            'meta' => ['session_id' => 'cs_test_forbidden'],
        ]);

        $response = $this->actingAs($user2)->getJson(route('payments.stripe.status', [
            'session_id' => 'cs_test_forbidden',
        ]));

        $response->assertForbidden();
    }

    public function test_unknown_session_id_returns_404(): void
    {
        Role::findOrCreate('admin');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('payments.stripe.status', [
            'session_id' => 'cs_test_unknown',
        ]));

        $response->assertNotFound();
    }
}
