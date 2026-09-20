<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';
require __DIR__ . '/mpesa.php';
require __DIR__ . '/auth.php';

$config = require __DIR__ . '/config.php';
$user = getLoggedInUser($pdo);

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $amount = (float) ($_POST['amount'] ?? 0);
    $phoneInput = trim($_POST['phone'] ?? '');

    if ($amount <= 0) {
        throw new Exception('Enter a valid deposit amount.');
    }

    $phone = normalizePhone($phoneInput);
    $orderId = 'DEP' . date('YmdHis') . random_int(1000, 9999);

    $pdo->beginTransaction();

    // 1. Create Pending Wallet Transaction
    $stmtW = $pdo->prepare('INSERT INTO wallet_transactions (user_id, type, amount, status, transaction_reference) VALUES (?, "deposit", ?, "pending", ?)');
    $stmtW->execute([$user['id'], $amount, $orderId]);

    // 2. Create Pending M-PESA Log
    $stmtM = $pdo->prepare('INSERT INTO mpesa_transactions (user_id, order_id, phone, amount, status) VALUES (?, ?, ?, ?, "PENDING")');
    $stmtM->execute([$user['id'], $orderId, $phone, $amount]);
    $transactionId = $pdo->lastInsertId();

    $pdo->commit();

    // 3. Initiate M-PESA STK Push
    $response = initiateStkPush($config, $phone, $amount, $orderId, 'Wallet Deposit');

    // 4. Update Checkout Request ID
    $update = $pdo->prepare('UPDATE mpesa_transactions SET merchant_request_id = ?, checkout_request_id = ?, response_code = ?, response_description = ? WHERE id = ?');
    $update->execute([
        $response['MerchantRequestID'] ?? null,
        $response['CheckoutRequestID'] ?? null,
        $response['ResponseCode'] ?? null,
        $response['ResponseDescription'] ?? null,
        $transactionId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'STK Push sent to your phone. Enter your PIN to complete deposit.',
        'order_id' => $orderId
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}