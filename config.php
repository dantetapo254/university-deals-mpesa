<?php
// M-PESA / Database configuration.
// NEVER commit real Consumer Secret or Passkey to GitHub.

return [
    'mpesa' => [
        // Use 'sandbox' while testing, then change to 'production'.
        'environment' => 'sandbox',

        'consumer_key' => '3RxJjJ4lNNjcxnya1o0d56RIIf9cH48znxU9l4OGDS9KQBwg',
        'consumer_secret' => 'IAeTl2fQgV4T14FAXebrNb6RzZ2ihTeI8E94azcAuuUVVgSkzeS1pgr8ePAmKLSh',

        // Your M-PESA PayBill/Till shortcode supplied by Safaricom.
        'shortcode' => '174379',
        'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',

        // MUST be publicly reachable by Safaricom in production.
        // Example: https://yourdomain.com/mpesa/callback.php
        'callback_url' => 'https://university-deals-mpesa.vercel.app/mpesa/callback.php',

      
        'transaction_desc' => 'Dante Project Payment',
    ],

  'database' => [
        // Environment variables take priority when configured by the host.
        'host' => getenv('DB_HOST') ?: 'sql301.infinityfree.com',
        'name' => getenv('DB_NAME') ?: 'if0_42965045_university_deals',
        'user' => getenv('DB_USER') ?: 'if0_42965045',
        'password' => getenv('DB_PASSWORD') ?: 'XWw9x4nwxOl',
        'charset' => 'utf8mb4',
    ],
];
php?>
 

