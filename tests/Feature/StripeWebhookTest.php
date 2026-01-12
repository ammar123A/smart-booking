<?php

namespace Tests\Feature;

use App\Jobs\FinalizeBookingPayment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_marks_payment_paid_logs_event_and_dispatches_finalize_job(): void
    {
        Bus::fake();

        $customer = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'Consultation',
            'description' => null,
            'active' => true,
        ]);

        $price = ServicePrice::query()->create([
            'service_id' => $service->id,
            'name' => '30 min',
            'duration_min' => 30,
            'amount' => 10000,
            'currency' => 'MYR',
            'active' => true,
        ]);

        $staff = Staff::query()->create([
            'name' => 'Alice',
            'user_id' => null,
            'timezone' => 'Asia/Kuala_Lumpur',
            'active' => true,
        ]);

        $service->staff()->sync([$staff->id]);

        StaffSchedule::query()->create([
            'staff_id' => $staff->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'timezone' => 'Asia/Kuala_Lumpur',
            'active' => true,
        ]);

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_PENDING_PAYMENT,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
            'expires_at' => now()->addMinutes(10),
            'total_amount' => 10000,
            'currency' => 'MYR',
        ]);

        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_ref' => 'stripe_ref_123',
            'status' => Payment::STATUS_PENDING,
            'amount' => 10000,
            'currency' => 'MYR',
            'paid_at' => null,
            'meta' => ['session_id' => 'cs_test_123'],
        ]);

        config()->set('stripe.webhook_secret', '');

        $payload = json_encode([
            'id' => 'evt_test_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_123',
                    'metadata' => [
                        'payment_id' => (string) $payment->id,
                        'booking_id' => (string) $booking->id,
                    ],
                ],
            ],
        ]);

        $this->postJson(route('payments.stripe.webhook'), json_decode($payload, true))
            ->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseCount('payment_events', 1);

        Bus::assertDispatched(FinalizeBookingPayment::class, function (FinalizeBookingPayment $job) use ($payment) {
            return $job->paymentId === $payment->id;
        });
    }

    public function test_webhook_with_no_payment_id_creates_event_but_does_not_update_payment(): void
    {
        config()->set('stripe.webhook_secret', '');

        $payload = json_encode([
            'id' => 'evt_test_456',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_456',
                    'payment_status' => 'paid',
                    'metadata' => [],
                ],
            ],
        ]);

        $this->postJson(route('payments.stripe.webhook'), json_decode($payload, true))
            ->assertOk();

        $this->assertDatabaseCount('payment_events', 1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_webhook_with_unknown_payment_id_creates_event(): void
    {
        config()->set('stripe.webhook_secret', '');

        $payload = json_encode([
            'id' => 'evt_test_789',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_789',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'payment_id' => '99999',
                        'booking_id' => '99999',
                    ],
                ],
            ],
        ]);

        $this->postJson(route('payments.stripe.webhook'), json_decode($payload, true))
            ->assertOk();

        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_webhook_does_not_update_already_paid_payment(): void
    {
        Bus::fake();

        $customer = User::factory()->create();
        $service = Service::create(['name' => 'Service', 'active' => true]);
        $price = ServicePrice::create([
            'service_id' => $service->id,
            'name' => '30 min',
            'duration_min' => 30,
            'amount' => 10000,
            'currency' => 'MYR',
            'active' => true,
        ]);
        $staff = Staff::create(['name' => 'Staff', 'timezone' => 'UTC', 'active' => true]);
        $service->staff()->attach($staff->id);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_price_id' => $price->id,
            'status' => Booking::STATUS_CONFIRMED,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'expires_at' => null,
            'total_amount' => 10000,
            'currency' => 'MYR',
        ]);

        $paidAt = now()->subMinutes(5);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'provider' => Payment::PROVIDER_STRIPE,
            'provider_ref' => 'stripe_ref_999',
            'status' => Payment::STATUS_PAID,
            'amount' => 10000,
            'currency' => 'MYR',
            'paid_at' => $paidAt,
            'meta' => ['session_id' => 'cs_test_999'],
        ]);

        config()->set('stripe.webhook_secret', '');

        $payload = json_encode([
            'id' => 'evt_test_999',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_999',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_999',
                    'metadata' => [
                        'payment_id' => (string) $payment->id,
                        'booking_id' => (string) $booking->id,
                    ],
                ],
            ],
        ]);

        $this->postJson(route('payments.stripe.webhook'), json_decode($payload, true))
            ->assertOk();

        $payment->refresh();
        $this->assertTrue($payment->paid_at->eq($paidAt));
        Bus::assertNotDispatched(FinalizeBookingPayment::class);
    }
}
