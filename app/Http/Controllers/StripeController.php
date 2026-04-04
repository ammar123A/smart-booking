<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizeBookingPayment;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\StripeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Exception\SignatureVerificationException;

class StripeController extends Controller
{
    public function initiate(Request $request, Booking $booking, StripeService $stripe)
    {
        $user = $request->user();
        if ($booking->customer_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403);
        }

        $now = CarbonImmutable::now('UTC');
        
        // Debug logging
        \Log::info('Payment initiation attempt', [
            'booking_id' => $booking->id,
            'booking_status' => $booking->status,
            'booking_expires_at' => $booking->expires_at?->toIso8601String(),
            'now_utc' => $now->toIso8601String(),
            'is_expired' => $booking->expires_at ? $booking->expires_at->lte($now) : false,
        ]);
        
        if ($booking->status !== Booking::STATUS_PENDING_PAYMENT) {
            return response()->json(['message' => 'Booking is not pending payment.'], 422);
        }

        if ($booking->expires_at && $booking->expires_at->lte($now)) {
            return response()->json(['message' => 'Booking has expired.'], 422);
        }

        $payment = $booking->payments()
            ->where('provider', Payment::PROVIDER_STRIPE)
            ->whereIn('status', [Payment::STATUS_INITIATED, Payment::STATUS_PENDING])
            ->latest('id')
            ->first();

        if (! $payment) {
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'provider' => Payment::PROVIDER_STRIPE,
                'provider_ref' => $stripe->generateProviderRef(),
                'status' => Payment::STATUS_INITIATED,
                'amount' => (int) $booking->total_amount,
                'currency' => (string) $booking->currency,
                'meta' => null,
            ]);
        }

        try {
            $session = $stripe->createCheckoutSession($booking, $payment);

            // Update payment with session ID
            $payment->meta = array_merge((array) $payment->meta, [
                'session_id' => $session->id,
            ]);
            $payment->status = Payment::STATUS_PENDING;
            $payment->save();

            if (! $request->expectsJson()) {
                return redirect()->away($session->url);
            }

            return response()->json([
                'data' => [
                    'payment_id' => $payment->id,
                    'provider' => $payment->provider,
                    'provider_ref' => $payment->provider_ref,
                    'status' => $payment->status,
                    'session_id' => $session->id,
                    'payment_url' => $session->url,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create payment session: ' . $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request, StripeService $stripe)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        if (empty($signature)) {
            return response()->json(['ok' => false, 'message' => 'Missing signature.'], 400);
        }

        try {
            $event = $stripe->constructWebhookEvent($payload, $signature);
        } catch (SignatureVerificationException $e) {
            return response()->json(['ok' => false, 'message' => 'Invalid signature.'], 400);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Webhook error.'], 400);
        }

        // Handle the event
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $paymentId = $session->metadata->payment_id ?? null;
            $bookingId = $session->metadata->booking_id ?? null;

            if (! $paymentId) {
                PaymentEvent::create([
                    'payment_id' => null,
                    'provider' => Payment::PROVIDER_STRIPE,
                    'provider_ref' => $session->id,
                    'event_type' => 'webhook_no_payment_id',
                    'payload_hash' => hash('sha256', $payload),
                    'payload' => json_decode($payload, true),
                    'received_at' => now(),
                ]);

                return response()->json(['ok' => true], 200);
            }

            /** @var Payment|null $payment */
            $payment = Payment::query()->find($paymentId);

            if (! $payment) {
                PaymentEvent::create([
                    'payment_id' => null,
                    'provider' => Payment::PROVIDER_STRIPE,
                    'provider_ref' => $session->id,
                    'event_type' => 'webhook_unknown_payment',
                    'payload_hash' => hash('sha256', $payload),
                    'payload' => json_decode($payload, true),
                    'received_at' => now(),
                ]);

                return response()->json(['ok' => true], 200);
            }

            $payloadHash = hash('sha256', $payload);

            PaymentEvent::firstOrCreate(
                [
                    'provider' => $payment->provider,
                    'provider_ref' => $session->id,
                    'event_type' => 'webhook',
                    'payload_hash' => $payloadHash,
                ],
                [
                    'payment_id' => $payment->id,
                    'payload' => json_decode($payload, true),
                    'received_at' => now(),
                ]
            );

            if ($payment->status !== Payment::STATUS_PAID) {
                $payment->status = Payment::STATUS_PAID;
                $payment->paid_at = now();
                $payment->meta = array_merge((array) $payment->meta, [
                    'session_id' => $session->id,
                    'payment_intent' => $session->payment_intent,
                    'payment_status' => $session->payment_status,
                ]);
                $payment->save();

                FinalizeBookingPayment::dispatch($payment->id);
            }
        }

        return response()->json(['ok' => true], 200);
    }

    public function success(Request $request, StripeService $stripe)
    {
        $sessionId = (string) $request->query('session_id', '');

        if ($sessionId === '') {
            return Inertia::render('PaymentReturn', [
                'session_id' => null,
                'booking_id' => null,
                'payment_status' => null,
                'message' => 'No session ID provided.',
            ]);
        }

        try {
            $session = $stripe->retrieveSession($sessionId);

            $paymentId = $session->metadata->payment_id ?? null;
            $bookingId = $session->metadata->booking_id ?? null;

            $payment = $paymentId ? Payment::find($paymentId) : null;

            // Fallback: if webhook hasn't fired yet (e.g. local dev), finalize now
            if ($payment && $session->payment_status === 'paid' && $payment->status !== Payment::STATUS_PAID) {
                $payment->status = Payment::STATUS_PAID;
                $payment->paid_at = now();
                $payment->meta = array_merge((array) $payment->meta, [
                    'session_id' => $session->id,
                    'payment_intent' => $session->payment_intent,
                    'payment_status' => $session->payment_status,
                    'finalized_via' => 'success_redirect',
                ]);
                $payment->save();

                FinalizeBookingPayment::dispatchSync($payment->id);
            }

            return Inertia::render('PaymentReturn', [
                'session_id' => $sessionId,
                'booking_id' => $bookingId,
                'payment_id' => $paymentId,
                'payment_status' => $payment?->fresh()?->status ?? $session->payment_status,
                'message' => $session->payment_status === 'paid' ? 'Payment successful!' : null,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('PaymentReturn', [
                'session_id' => $sessionId,
                'booking_id' => null,
                'payment_status' => 'error',
                'message' => 'Failed to retrieve payment details.',
            ]);
        }
    }

    public function cancel(Request $request)
    {
        $bookingId = (string) $request->query('booking_id', '');

        return Inertia::render('PaymentReturn', [
            'session_id' => null,
            'booking_id' => $bookingId !== '' ? $bookingId : null,
            'payment_status' => 'cancelled',
            'message' => 'Payment was cancelled.',
        ]);
    }

    public function status(Request $request)
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string'],
        ]);

        $sessionId = (string) $validated['session_id'];

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->with(['booking'])
            ->where('provider', Payment::PROVIDER_STRIPE)
            ->whereRaw("meta->>'session_id' = ?", [$sessionId])
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Unknown session.'], 404);
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
                'session_id' => $sessionId,
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
                'booking_id' => $booking->id,
                'booking_status' => $booking->status,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ],
        ]);
    }
}
