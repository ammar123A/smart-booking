<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizeBookingPayment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\SenangPayService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class SenangPayController extends Controller
{
    public function initiate(Request $request, Booking $booking, SenangPayService $senangPay)
    {
        $user = $request->user();
        if ($booking->customer_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403);
        }

        $now = CarbonImmutable::now('UTC');
        if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
            return response()->json(['message' => 'Booking is not pending payment.'], 422);
        }

        if ($booking->expires_at && $booking->expires_at->lte($now)) {
            return response()->json(['message' => 'Booking has expired.'], 422);
        }

        $payment = $booking->payments()
            ->where('provider', Payment::PROVIDER_SENANGPAY)
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
            ->latest('id')
            ->first();

        if (! $payment) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'provider' => Payment::PROVIDER_SENANGPAY,
                'provider_ref' => $senangPay->generateProviderRef(),
                'status' => Payment::STATUS_INITIATED,
                'amount' => (int) $booking->total_amount,
                'currency' => (string) $booking->currency,
                'meta' => null,
            ]);
        }

        $merchantId = (string) config('senangpay.merchant_id');
        $detailPrefix = (string) config('senangpay.detail_prefix');

        $params = [
            'merchant_id' => $merchantId,
            'order_id' => $payment->provider_ref,
            'detail' => trim($detailPrefix.' #'.$booking->id),
            // SenangPay amount formatting may differ; keep raw integer cents in DB and send formatted here.
            'amount' => number_format($payment->amount / 100, 2, '.', ''),
            'name' => (string) ($booking->customer?->name ?? ''),
            'email' => (string) ($booking->customer?->email ?? ''),
        ];

        $paymentUrl = $senangPay->buildPaymentUrl($params);

        if (! $request->expectsJson()) {
            return redirect()->away($paymentUrl);
        }

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
                'provider_ref' => $payment->provider_ref,
                'status' => $payment->status,
                'payment_url' => $paymentUrl,
            ],
        ]);
    }

    public function callback(Request $request, SenangPayService $senangPay)
    {
        $payload = $request->all();
        $orderId = (string) Arr::get($payload, 'order_id', '');

        if ($orderId === '') {
            return response()->json(['ok' => false, 'message' => 'Missing order_id.'], 200);
        }

        if (! $senangPay->verifyCallbackSignature($payload)) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature.'], 200);
        }

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('provider', Payment::PROVIDER_SENANGPAY)
            ->where('provider_ref', $orderId)
            ->first();

        if (! $payment) {
            PaymentEvent::create([
                'payment_id' => null,
                'provider' => Payment::PROVIDER_SENANGPAY,
                'provider_ref' => $orderId,
                'event_type' => 'callback_unknown_order',
                'payload_hash' => hash('sha256', json_encode($this->normalizePayload($payload))),
                'payload' => $this->normalizePayload($payload),
                'received_at' => now(),
            ]);

            return response()->json(['ok' => true, 'message' => 'Ignored.'], 200);
        }

        $statusId = (string) Arr::get($payload, 'status_id', '');
        $nextStatus = $senangPay->mapCallbackToPaymentStatus($statusId);

        $normalized = $this->normalizePayload($payload);
        $payloadHash = hash('sha256', json_encode($normalized));

        PaymentEvent::firstOrCreate(
            [
                'provider' => $payment->provider,
                'provider_ref' => $payment->provider_ref,
                'event_type' => 'callback',
                'payload_hash' => $payloadHash,
            ],
            [
                'payment_id' => $payment->id,
                'payload' => $normalized,
                'received_at' => now(),
            ]
        );

        if ($payment->status !== Payment::STATUS_PAID) {
            $payment->status = $nextStatus;
            $payment->meta = array_merge((array) $payment->meta, [
                'status_id' => $statusId,
                'transaction_id' => (string) Arr::get($payload, 'transaction_id', ''),
                'msg' => (string) Arr::get($payload, 'msg', ''),
            ]);

            if ($nextStatus === Payment::STATUS_PAID && ! $payment->paid_at) {
                $payment->paid_at = now();
            }

            $payment->save();
        }

        if ($payment->status === Payment::STATUS_PAID) {
            FinalizeBookingPayment::dispatch($payment->id);
        }

        return response()->json(['ok' => true], 200);
    }

    public function returned(Request $request)
    {
        $orderId = (string) $request->query('order_id', '');
        $statusId = (string) $request->query('status_id', '');
        $transactionId = (string) $request->query('transaction_id', '');
        $msg = (string) $request->query('msg', '');

        $payment = null;
        $booking = null;

        if ($orderId !== '') {
            $payment = Payment::query()
                ->with(['booking'])
                ->where('provider', Payment::PROVIDER_SENANGPAY)
                ->where('provider_ref', $orderId)
                ->first();

            $booking = $payment?->booking;
        }

        // If the gateway doesn't send order_id on return, fall back to a simple message.
        $bookingId = $booking?->id;

        return Inertia::render('PaymentReturn', [
            'order_id' => $orderId !== '' ? $orderId : null,
            'booking_id' => $bookingId,
            'payment_status' => $payment?->status,
            'status_id' => $statusId !== '' ? $statusId : null,
            'transaction_id' => $transactionId !== '' ? $transactionId : null,
            'message' => $msg !== '' ? $msg : null,
        ]);
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
        ]);

        $orderId = (string) $validated['order_id'];

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->with(['booking'])
            ->where('provider', Payment::PROVIDER_SENANGPAY)
            ->where('provider_ref', $orderId)
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Unknown order_id.'], 404);
        }

        $booking = $payment->booking;
        if (! $booking) {
            return response()->json(['message' => 'Payment has no booking.'], 404);
        }

        $user = $request->user();
        if ($booking->customer_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403);
        }

        return response()->json([
            'data' => [
                'order_id' => $payment->provider_ref,
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
                'booking_id' => $booking->id,
                'booking_status' => $booking->status,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        ksort($payload);
        return $payload;
    }
}
