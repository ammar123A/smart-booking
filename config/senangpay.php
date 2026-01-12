<?php

return [
    'merchant_id' => env('SENANGPAY_MERCHANT_ID'),
    'secret_key' => env('SENANGPAY_SECRET_KEY'),

    // When enabled, callbacks must pass signature verification.
    // Disable locally/tests until you've confirmed the exact SenangPay signature fields.
    'verify_callback_signature' => (bool) env('SENANGPAY_VERIFY_CALLBACK_SIGNATURE', false),

    // Base URL for hosted payment page.
    // Replace with SenangPay production/sandbox URLs as needed.
    'payment_base_url' => env('SENANGPAY_PAYMENT_BASE_URL', 'https://app.senangpay.my/payment'),

    'detail_prefix' => env('SENANGPAY_DETAIL_PREFIX', 'Booking'),
];
