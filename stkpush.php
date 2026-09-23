<?php
header('Content-Type: application/json');

// 1. Load configuration
$config = require_once __DIR__ . '/config.php';

$mpesaConfig = $config['mpesa'];
$env = $mpesaConfig['environment'];

// Determine API base URL based on environment
$baseUrl = ($env === 'production') 
    ? 'https://api.safaricom.co.ke' 
    : 'https://sandbox.safaricom.co.ke';

// 2. Get input data (Sanitized & Normalized)
$phone = $_POST['phone'] ?? $_GET['phone'] ?? null;
$amount = $_POST['amount'] ?? $_GET['amount'] ?? null;

if (!$phone || !$amount) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Phone number and amount are required.'
    ]);
    exit;
}

// Format phone number to 2547XXXXXXXX or 2541XXXXXXXX
$phone = preg_replace('/^0/', '254', trim($phone));
$phone = preg_replace('/^\+/', '', $phone);

if (!preg_replace('/^254[71][0-9]{8}$/', '', $phone) === '') {
    // Basic regex check for valid Safaricom/Kenyan format
    if (strlen($phone) !== 12 || !str_starts_with($phone, '254')) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid phone number format. Use 254XXXXXXXXX or 07XXXXXXXX.'
        ]);
        exit;
    }
}

// 3. Generate OAuth Access Token
$credentials = base64_encode($mpesaConfig['consumer_key'] . ':' . $mpesaConfig['consumer_secret']);

$ch = curl_init($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to reach Daraja API: ' . $curlError
    ]);
    exit;
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve access token.',
        'details' => $tokenData
    ]);
    exit;
}

// 4. Prepare STK Push Request Parameters
$timestamp = date('YmdHis');
$password = base64_encode($mpesaConfig['shortcode'] . $mpesaConfig['passkey'] . $timestamp);

$stkPushData = [
    'BusinessShortCode' => $mpesaConfig['shortcode'],
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => (int)$amount,
    'PartyA'            => $phone,
    'PartyB'            => $mpesaConfig['shortcode'],
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $mpesaConfig['callback_url'],
    'AccountReference'  => 'Deposit',
    'TransactionDesc'   => 'Deposit Payment'
];

// 5. Send STK Push Request
$ch = curl_init($baseUrl . '/mpesa/stkpush/v1/processrequest');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($stkPushData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$stkResponse = curl_exec($ch);
curl_close($ch);

$stkData = json_decode($stkResponse, true);

// 6. Return standard response
if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] == '0') {
    echo json_encode([
        'status' => 'success',
        'message' => 'STK Push sent successfully. Please check your phone.',
        'MerchantRequestID' => $stkData['MerchantRequestID'],
        'CheckoutRequestID' => $stkData['CheckoutRequestID']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $stkData['ResponseDescription'] ?? 'Failed to initiate STK Push.',
        'raw_response' => $stkData
    ]);
}