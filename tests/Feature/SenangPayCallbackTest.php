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

class SenangPayCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_marks_payment_paid_logs_event_and_dispatches_finalize_job(): void
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
            'provider' => Payment::PROVIDER_SENANGPAY,
            'provider_ref' => 'ORDER123',
            'status' => Payment::STATUS_PENDING,
            'amount' => 10000,
            'currency' => 'MYR',
            'paid_at' => null,
            'meta' => [],
        ]);

        config()->set('senangpay.verify_callback_signature', false);

        $payload = [
            'status_id' => '1',
            'order_id' => 'ORDER123',
            'transaction_id' => 'TXN999',
            'msg' => 'OK',
        ];

        $this->postJson(route('payments.senangpay.callback'), $payload)->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseCount('payment_events', 1);

        Bus::assertDispatched(FinalizeBookingPayment::class, function (FinalizeBookingPayment $job) use ($payment) {
            return (int) $job->paymentId === (int) $payment->id;
        });

        // Idempotency: same callback should not create a duplicate event.
        $this->postJson(route('payments.senangpay.callback'), $payload)->assertOk();
        $this->assertDatabaseCount('payment_events', 1);
    }

    public function test_callback_confirms_booking_when_queue_is_sync(): void
    {
        config()->set('queue.default', 'sync');

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
            'provider' => Payment::PROVIDER_SENANGPAY,
            'provider_ref' => 'ORDER_SYNC',
            'status' => Payment::STATUS_PENDING,
            'amount' => 10000,
            'currency' => 'MYR',
            'paid_at' => null,
            'meta' => [],
        ]);

        config()->set('senangpay.verify_callback_signature', false);

        $payload = [
            'status_id' => '1',
            'order_id' => 'ORDER_SYNC',
            'transaction_id' => 'TXN_SYNC',
            'msg' => 'OK',
        ];

        $this->postJson(route('payments.senangpay.callback'), $payload)->assertOk();

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);

        // Idempotent on retries.
        $this->postJson(route('payments.senangpay.callback'), $payload)->assertOk();
        $this->assertDatabaseCount('payment_events', 1);
    }
}
