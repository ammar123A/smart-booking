<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    public function generateProviderRef(): string
    {
        return 'stripe-'.Str::ulid()->toBase32();
    }

    /**
     * Create a Stripe Checkout Session for a booking
     */
    public function createCheckoutSession(Booking $booking, Payment $payment): Session
    {
        $servicePrice = $booking->servicePrice;
        $service = $servicePrice->service;

        // Convert amount to smallest currency unit (cents for MYR)
        $amountInCents = (int) $servicePrice->amount;

        $session = Session::create([
            'payment_method_types' => ['card', 'fpx'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($servicePrice->currency),
                    'product_data' => [
                        'name' => $service->name . ' - ' . $servicePrice->name,
                        'description' => "Booking #{$booking->id} - {$booking->starts_at->format('M d, Y H:i')}",
                    ],
                    'unit_amount' => $amountInCents,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => url(config('stripe.success_url')) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url(config('stripe.cancel_url')) . '?booking_id=' . $booking->id,
            'client_reference_id' => (string) $booking->id,
            'metadata' => [
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'provider_ref' => $payment->provider_ref,
            ],
        ]);

        return $session;
    }

    /**
     * Verify Stripe webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('stripe.webhook_secret');

        if (empty($webhookSecret)) {
            return false;
        }

        try {
            Webhook::constructEvent($payload, $signature, $webhookSecret);
            return true;
        } catch (SignatureVerificationException $e) {
            return false;
        }
    }

    /**
     * Construct webhook event from payload
     */
    public function constructWebhookEvent(string $payload, string $signature)
    {
        $webhookSecret = config('stripe.webhook_secret');

        return Webhook::constructEvent($payload, $signature, $webhookSecret);
    }

    /**
     * Retrieve a checkout session by ID
     */
    public function retrieveSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }

    /**
     * Map Stripe payment status to our payment status
     */
    public function mapStripeStatusToPaymentStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'paid', 'complete' => Payment::STATUS_PAID,
            'open', 'processing' => Payment::STATUS_PENDING,
            default => Payment::STATUS_FAILED,
        };
    }

    /**
     * Check if payment status is successful
     */
    public function isPaidStatus(string $status): bool
    {
        return in_array($status, ['paid', 'complete', 'succeeded']);
    }
}
