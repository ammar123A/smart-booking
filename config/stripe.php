<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable and secret keys from your Stripe account.
    |
    */

    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),

    'secret_key' => env('STRIPE_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook signing secret for verifying webhook events from Stripe.
    |
    */

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for Stripe payments.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'myr'),

    /*
    |--------------------------------------------------------------------------
    | Success and Cancel URLs
    |--------------------------------------------------------------------------
    |
    | URLs for redirecting after payment completion or cancellation.
    |
    */

    'success_url' => env('STRIPE_SUCCESS_URL', '/payments/stripe/success'),

    'cancel_url' => env('STRIPE_CANCEL_URL', '/payments/stripe/cancel'),

];
