<?php
// M-PESA / Database configuration.
// NEVER commit real Consumer Secret or Passkey to GitHub.

return [
    'mpesa' => [
        // Use 'sandbox' while testing, then change to 'production'.
        'environment' => 'sandbox',

        'consumer_key' => 'xpEA6biO5A3x8dier4qUDn1LXQOgTiz4rDUN9dMZ7PFVLSll',
        'consumer_secret' => 'DuZrbyTKgb273YzSDBBggINjbNixg5jna0mmVMSkeqBEqx7QmlJtU3qXDdVCzMmF',

        // Your M-PESA PayBill/Till shortcode supplied by Safaricom.
        'shortcode' => '174379',
        'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',

        // MUST be publicly reachable by Safaricom in production.
        // Example: https://yourdomain.com/mpesa/callback.php
        'callback_url' => 'https://university-deals-mpesa.vercel.app/mpesa/callback.php',

        // Name shown in the checkout description.
        'transaction_desc' => 'Dante Project Payment',
    ],

    'database' => [
        'host' => 'localhost',
        'name' => 'university_deals',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
